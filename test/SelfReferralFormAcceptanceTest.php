<?php
namespace Test\MayReferralTest\Acceptance;

use \PDO;

class SelfReferralFormAcceptanceTest extends \PHPUnit_Extensions_Selenium2TestCase
{

    public $uuid;
    const SAY_YES = 'Yes';
    const JUST_SAY_NO = 'No';

    public function setUp()
    {
        $this->setHost('192.168.20.1');
        $this->setPort(4444);
        $this->setBrowser('firefox');
        $this->setBrowserUrl('http://test.paywall.vagrant/referral-v2/');
        $this->uuid = '6910ef81-53f8-4428-b37a-32ca7f10e4fc';
    }

    public function testRequiredFrontEnd()
    {
        $this->url($this->uuid);
        $firstRecord = $this->fetchFormData($this->uuid);
        $formSpec = json_decode($firstRecord['spec'], true);
        foreach ($formSpec['elements'] as $elementSpec) {
            if (isset($elementSpec['spec']['attributes']['required'])) {
                $element = $this->byName($elementSpec['spec']['name']);
                $isRequired = $elementSpec['spec']['attributes']['required'];
                echo 'Checking frontend that ' . $element->attribute('name') . ' is ' . $isRequired . PHP_EOL;
                $this->assertEquals((bool)$element->attribute('required'),
                    (bool)$isRequired);  // todo show red on failure  and don't check hidden,note text etc
            }
        }
    }

    public function testSubmittedYesValuesInIaptus()
    {
        $this->url($this->uuid);
        $firstRecord = $this->fetchFormData($this->uuid);
        $successMessage = $firstRecord['successMessage'];
        $formTitle = $firstRecord['title'] . '-Yes';
        $formSpec = json_decode($firstRecord['spec'], true);
        $this->submitValidForm($formSpec, $formTitle, self::SAY_YES, $successMessage);
        system(__DIR__ . '/runCrons.sh');
    }

    public function testSubmittedNoValuesInIaptus()
    {
        $this->url($this->uuid);
        $firstRecord = $this->fetchFormData($this->uuid);
        $successMessage = $firstRecord['successMessage'];
        $formTitle = $firstRecord['title'] . '-No';
        $formSpec = json_decode($firstRecord['spec'], true);

        $this->submitValidForm($formSpec, $formTitle, self::JUST_SAY_NO, $successMessage);
        system(__DIR__ . '/runCrons.sh');
    }

    /**
     * @param $formSpec
     * @return mixed
     */
    public function checkFrontEndRequired($formSpec)
    {

    }


    public function submitValidForm($formSpec, $formTitle, $yesOrNo, $successMessage)
    {
        $submittedValues = [];
        $ifKeyTrueThenRequired = [];
        $ifKeyIsNoThenMustBeBlank = [];
        foreach ($formSpec['input_filter'] as $filter) {
            if (isset($filter['validators'])) {
                foreach ($filter['validators'] as $validator) {
                    if ($validator['name'] === '\\Mayden\\Form\\Validator\\ConditionalRequirementValidator') {
                        $ifKeyTrueThenRequired[$filter['name']] = $validator['options']['field'];
                    }
                    if ($validator['name'] === 'Mayden\\Form\\Validator\\ConditionalRequirementSpecifiedValuesValidator') {
                        $ifKeyIsNoThenMustBeBlank[$filter['name']] = $validator['options']['field'];
                    }
                }
            }
        }
        foreach ($formSpec['elements'] as $key => $elementSpec) {
//            if ($yesOrNo === self::JUST_SAY_NO && in_array($elementSpec['spec']['name'], $ifKeyIsNoThenMustBeBlank)) {
//                $submittedValues[$elementSpec['spec']['name']] = ''; // this breaks stuff and needs rethinking
//                continue;
//            }
            if ($elementSpec['spec']['name'] === 'provided_nhs_number') {
                $this->byName($elementSpec['spec']['name'])->value('0123456789');
                $submittedValues[$elementSpec['spec']['name']] = '0123456789';
                continue;
            }
            if ($elementSpec['spec']['name'] === 'pat_postcode') {
                $this->byName($elementSpec['spec']['name'])->value('nr28 0ad');
                $submittedValues[$elementSpec['spec']['name']] = 'nr28 0ad';
                continue;
            }
            if ($elementSpec['spec']['name'] === 'pat_lastname') {
                $this->byName($elementSpec['spec']['name'])->value($yesOrNo);
                $submittedValues[$elementSpec['spec']['name']] = $yesOrNo;
                continue;
            }
            switch ($elementSpec['spec']['type']) {
                case 'Mayden\\Form\\Form\\Element\\NoteText':
                case 'Zend\\Form\\Element\\Hidden':
                case 'Mayden\\Form\\Form\\Element\\SectionHeading':
                    break;
                case 'Zend\\Form\\Element\\Select':
                    $valueSelected = current(array_keys($elementSpec['spec']['options']['value_options']));
                    \PHPUnit_Extensions_Selenium2TestCase_Element_Select::fromElement($this->byName($elementSpec['spec']['name']))
                        ->selectOptionByValue($valueSelected);
                    $submittedValues[$elementSpec['spec']['name']] = $valueSelected;
                    break;
                case 'Mayden\\Form\\Form\\Element\\DatePicker':
                    $aDateInThePast = '10/04/2016';
                    $this->byName($elementSpec['spec']['name'])->value($aDateInThePast);
                    $submittedValues[$elementSpec['spec']['name']] = '2016-04-10';
                    break;
                case 'Zend\\Form\\Element\\Radio':
                case 'Zend\\Form\\Element\\CheckBox':
                    $value_options = $elementSpec['spec']['options']['value_options'];
                    if (count($value_options) == 2 &&
                        in_array('Yes', $value_options) &&
                        in_array('No', $value_options)
                    ) {
                        $radioOption = array_search($yesOrNo, $elementSpec['spec']['options']['value_options']);
                        $this->execute([
                            'script' => '$("input[name=' . $elementSpec['spec']['name'] . ']").val(["' . $radioOption . '"])',
                            'args' => [],
                        ]);
                        $submittedValues[$elementSpec['spec']['name']] = [0, 1, 'Yes' => 1, 'No' => 0][$radioOption];
                    } else {
                        $radioOption = current(array_keys($elementSpec['spec']['options']['value_options']));
                        $this->execute([
                            'script' => '$("input[name=' . $elementSpec['spec']['name'] . ']").val(["' . $radioOption . '"])',
                            'args' => [],
                        ]);
                        $submittedValues[$elementSpec['spec']['name']] = $radioOption;

                    }
                    break;
                case '\\Mayden\\Form\\Form\\Element\\PostcodeUK':
                    $aPostCode = 'NR23 4RF';
                    $this->byName($elementSpec['spec']['name'])->value($aPostCode);
                    $submittedValues[$elementSpec['spec']['name']] = $aPostCode;
                    break;
                case '\\Mayden\\Form\\Form\\Element\\PhoneNumber':
                    $aPhoneNumber = '07234567890';
                    $this->byName($elementSpec['spec']['name'])->value($aPhoneNumber);
                    $submittedValues[$elementSpec['spec']['name']] = $aPhoneNumber;
                    break;
                case 'Mayden\\Form\\Form\\Element\\Email':
                    $anEmail = 'test@testyMcTestFace.com';
                    $this->byName($elementSpec['spec']['name'])->value($anEmail);
                    $submittedValues[$elementSpec['spec']['name']] = $anEmail;
                    break;
                case 'Zend\\Form\\Element\\Text':
                case 'Zend\\Form\\Element\\Textarea':
                    $this->byName($elementSpec['spec']['name'])->value($formTitle);
                    $submittedValues[$elementSpec['spec']['name']] = $formTitle;
                    break;
            }
        }


        $filedata = $this->currentScreenshot();
        file_put_contents(__DIR__ . '/../data/' . $formTitle . '-screenshot.png', $filedata);
        file_put_contents(__DIR__ . '/../data/' . $formTitle . '-values.php', serialize($submittedValues));
        //submit the form and test success
        try {
            $this->byId("SelfReferral")->submit();
            $this->assertEquals($successMessage, $this->byCssSelector('.alert li')->text());
        } catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            echo 'Selenium error code: ' . $e->getCode() . PHP_EOL;
            $filedata = $this->currentScreenshot();
            file_put_contents(__DIR__ . '/../data/' . $formTitle . '-ERROR-screenshot.png', $filedata);
        }

    }


    public function fetchFormData($uuid)
    {
        $dbPaywall = new PDO('mysql:host=192.168.20.99;dbname=paywall', 'root', '');
        $sql = 'SELECT spec, title, successMessage FROM form_configs
         JOIN form_metadata ON form_configs.uuid=form_metadata.formUuid
         WHERE uuid="' . $uuid . '";';
        $stmt = $dbPaywall->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    public function compareToIaptus()
    {

    }


    //remove disabled and required attributes on all elements to test backend validation
//        $this->execute(array(
//            'script' => "Bad javascript here",
//            'args' => array()
//        ));


}