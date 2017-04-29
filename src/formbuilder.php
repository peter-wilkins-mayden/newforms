<?php
ini_set('auto_detect_line_endings', true);
global $argv;
$title = 'Cumbria Self Referral Form';
$handle = fopen('data/cumbria.csv',
    'r'); // todo trim whitespace?

// Grab old config from db todo change old bacpac names in spec csv if they exist, check for other random validators and add them if sensible
//$configInDB = fetchFormConfig($title);
$oldConfig = include __DIR__. '/../src/cumbria-old-spec.php';
//    json_decode($configInDB['spec'], true);
//var_export($oldConfig);
//dumpOldSpec($oldConfig, $title);
//exit;

$oldValueOptions = [];
$oldValueOptionsOrder = [];
foreach ($oldConfig['elements'] as $element) {
    if (isset($element['spec']['options']['value_options'])) {
        $oldValueOptions[$element['spec']['name']] = $element['spec']['options']['value_options'];
    }
    if (isset($element['spec']['value_options_order'])) {
        $oldValueOptionsOrder[$element['spec']['name']] = $element['spec']['value_options_order'];
    }
}
//var_export($oldValueOptionsOrder);exit;
$spec = [
    'type' => 'Mayden\\Form\\Form\\DynamicForm',
    'elements' => [],
    'input_filter' => [],
];
$index = 0;
$elementIndex = [];
while (($element = fgetcsv($handle)) !== false) {
    //var_export($element);
    $elementIndex[$index] = $element[0];
    $index++;
}
//var_export($elementIndex);exit;
rewind($handle);
$index = 0;

function isSectionHeader($element)
{
    return !$element[1] && !$element[2] && !$element[3] && !$element[4];
}



function addDefaultConfig($element, $spec, $index)
{
    $spec['elements'][$index]['spec']['name'] = $element[0];
    $spec['elements'][$index]['flags']['priority'] = (string)-$index;
    return $spec;
}

function addTextBoxConfig($spec, $index, $element)
{
    $spec['elements'][$index]['spec']['type'] = 'Zend\\Form\\Element\\Text';
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['input_filter'][$index]['validators'][] = ['name' => '\\Mayden\\Form\\Validator\\SafeCharacters'];
    $spec['input_filter'][$index]['filters'][] = ['name' => '\\Zend\\Filter\\StringTrim',];
    return $spec;
}

function addDatePickerConfig($spec, $index, $element)
{
    $spec['elements'][$index]['spec']['type'] = 'Mayden\\Form\\Form\\Element\\DatePicker';
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['input_filter'][$index]['filters'][] = ['name' => '\\Zend\\Filter\\StringTrim'];
    $spec['input_filter'][$index]['validators'][] = [
        'name' => 'Date',
        'options' =>
            [
                'format' => 'd/m/Y',
                'break_chain_on_failure' => true,
            ],
    ];
    return $spec;
}

function addDropDownConfig($spec, $index, $element, $oldValueOptions, $oldValueOptionsOrder)
{
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['elements'][$index]['spec']['type'] = 'Zend\\Form\\Element\\Select';
    $spec['elements'][$index]['spec']['options']['empty_option'] = 'Please Select a Value...';
    if (key_exists($element[0], $oldValueOptions)) {
        $spec['elements'][$index]['spec']['options']['value_options'] = $oldValueOptions[$element[0]];
    } else {
        $spec['elements'][$index]['spec']['options']['value_options'] = preg_split("/\\r\\n|\\r|\\n/",
            $element[3]);
    }
    if (key_exists($element[0], $oldValueOptionsOrder)) {
        $spec['elements'][$index]['spec']['value_options_order'] = $oldValueOptionsOrder[$element[0]];
    }
    return $spec;  //todo get listIds
}

function addRadioConfig($spec, $index, $element, $oldValueOptions, $oldValueOptionsOrder)
{
    //todo list value keys
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['elements'][$index]['spec']['type'] = 'Zend\\Form\\Element\\Radio';
    if (key_exists($element[0], $oldValueOptions)) {
        $spec['elements'][$index]['spec']['options']['value_options'] = $oldValueOptions[$element[0]];
    } else {
        $spec['elements'][$index]['spec']['options']['value_options'] = preg_split("/\\r\\n|\\r|\\n/",
            $element[3]);
    }
    if (key_exists($element[0], $oldValueOptionsOrder)) {
        $spec['elements'][$index]['spec']['value_options_order'] = $oldValueOptionsOrder[$element[0]];
    }
    return $spec;  //todo get listIds
}

function addCheckBoxConfig($spec, $index, $element, $oldValueOptions, $oldValueOptionsOrder)
{
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['elements'][$index]['spec']['type'] = 'Zend\\Form\\Element\\MultiCheckBox';
    if (key_exists($element[0], $oldValueOptions)) {
        $spec['elements'][$index]['spec']['options']['value_options'] = $oldValueOptions[$element[0]];
    } else {
        $spec['elements'][$index]['spec']['options']['value_options'] = preg_split("/\\r\\n|\\r|\\n/",
            $element[3]);
    }
    if (key_exists($element[0], $oldValueOptionsOrder)) {
        $spec['elements'][$index]['spec']['value_options_order'] = $oldValueOptionsOrder[$element[0]];
    }
    return $spec;  //todo get listIds
}

function addTextAreaConfig($spec, $index, $element)
{
    $spec['elements'][$index]['spec']['type'] = 'Zend\\Form\\Element\\Textarea';
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['input_filter'][$index]['validators'][] = ['name' => '\\Mayden\\Form\\Validator\\SafeCharacters']; // todo default 500 chars?
    $spec['input_filter'][$index]['filters'][] = ['name' => '\\Zend\\Filter\\StringTrim',];
    return $spec;
}

function addPostCodeConfig($spec, $index)
{
    $spec['elements'][$index]['spec']['type'] = '\\Mayden\\Form\\Form\\Element\\PostcodeUK';
    return $spec;
}

function addMobileConfig($spec, $index, $element)
{
    $spec = addPhoneConfig($spec, $index, $element);
    $spec['input_filter'][$index]['validators'][] = ['name' => '\\Mayden\\Form\\Validator\\UnitedKingdomMobile',];
    return $spec;
}

function addPhoneConfig($spec, $index, $element)
{
    $spec['elements'][$index]['spec']['type'] = '\\Mayden\\Form\\Form\\Element\\PhoneNumber';
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['input_filter'][$index]['validators'][] = ['name' => '\\Mayden\\Form\\Validator\\PhoneNumberOrEmpty',];
    return $spec;
}

function addEmailConfig($spec, $index, $element)
{
    $spec['elements'][$index]['spec']['type'] = 'Mayden\\Form\\Form\\Element\\Email';
    $spec['input_filter'][$index]['name'] = $element[0];
    return $spec;
}

function addPastDateConfig($spec, $index, $element)
{
    $dateValidators = [
        'name' => '\\Mayden\\Form\\Validator\\IsPastDateValidator',
        'options' =>
            [
                'inclusive' => false,
                'comparison_date' => 'now',
                'timezone' => null,
                'format' => 'd/m/Y',
            ],
    ];
    $spec['elements'][$index]['spec']['type'] = 'Mayden\\Form\\Form\\Element\\DatePicker';
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['input_filter'][$index]['validators'][] = $dateValidators;
    return $spec;
}

function addFutureDateConfig($spec, $index, $element)
{
    $dateValidators = [
        'name' => '\\Mayden\\Form\\Validator\\IsFutureDateValidator',
        'options' =>
            [
                'inclusive' => false,
                'comparison_date' => 'now',
                'timezone' => null,
                'format' => 'd/m/Y',
            ],
    ];
    $spec['elements'][$index]['spec']['type'] = 'Mayden\\Form\\Form\\Element\\DatePicker';
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['input_filter'][$index]['validators'][] = $dateValidators;
    return $spec;
}

function addNHSConfig($spec, $index)
{
    $spec['input_filter'][$index]['validators'][] = [
                'name' => 'Zend\\Validator\\Digits',
                'options' =>
                    [
                        'breakchainonfailure' => false,
                    ],
            ];
    $spec['input_filter'][$index]['validators'][] =[
                'name' => 'Zend\\Validator\\StringLength',
                'options' =>
                    [
                        'min' => '10',
                        'max' => '10',
                    ],
            ];
    return $spec;
}


function addRequiredConfig($spec, $index, $element)
{
    $spec['elements'][$index]['spec']['attributes']['required'] = 'required';
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['input_filter'][$index]['required'] = true;
    return $spec;
}

function addNotRequiredConfig($spec, $index, $element)
{
    $spec['input_filter'][$index]['name'] = $element[0];
    $spec['input_filter'][$index]['required'] = false;
    return $spec;
}

function addEitherOrConfig($element, $spec, $orMatches, $elementIndex)
{
    $spec['input_filter'][array_search($orMatches[1], $elementIndex)]['validators'][] = [
        'name' => '\\Mayden\\Form\\Validator\\EitherOrBothValidator',
        'options' => [
            'field' => $element[0],
        ],
    ];
    return $spec;
}

function addMustMatchConfig($element, $spec, $mustMatches, $elementIndex)
{
    $spec['input_filter'][array_search($mustMatches[1], $elementIndex)]['validators'][] = [
        'name' => 'Mayden\\Form\\Validator\\LinkedFieldValueValidator',
        'options' => [
            'field' => $element[0],
        ],
    ];
    return $spec;
}

function addIfOtherEqualsTrueConfig($element, $spec, $ifMatches, $elementIndex, $index)
{
    $spec['elements'][$index]['spec']['attributes']['required'] = false;
    $spec['input_filter'][$index]['required'] = false;
    $spec['input_filter'][array_search($ifMatches[1], $elementIndex)]['validators'][] = [
        'name' => '\\Mayden\\Form\\Validator\\ConditionalRequirementValidator',
        'options' => [
            'formName' => 'SelfReferral',
            'field' => $element[0],
        ],
    ];
    return $spec;
}

function addIfOtherEqualsValueConfig($element, $ifMatches, $spec, $elementIndex, $index)
{
    $spec['elements'][$index]['spec']['attributes']['required'] = false;
    $spec['input_filter'][$index]['required'] = false;
    $spec['input_filter'][array_search($ifMatches[1], $elementIndex)]['validators'][] = [
        'name' => 'Mayden\\Form\\Validator\\ConditionalRequirementSpecifiedValuesValidator',
        'options' => [
            'formName' => 'SelfReferral',
            'field' => $element[0],
            'check_value' => $ifMatches[2],  // todo fix this hack when keys sorted
        ],
    ];
    return $spec;
}

function addSectionHeaderConfig($spec, $index)
{
    $spec['elements'][$index]['spec']['type'] = 'Mayden\\Form\\Form\\Element\\SectionHeading';
    return $spec;
}

function addMessageNoteTextConfig($spec, $index)
{
    $spec['elements'][$index]['spec']['type'] = 'Mayden\\Form\\Form\\Element\\NoteText'; // todo bug in formbuilder if 2 notetext names identical only one is displayed
    return $spec;
}

while (($element = fgetcsv($handle)) !== false) {

    $spec = addDefaultConfig($element, $spec, $index);

    if (isSectionHeader($element)) {
        $spec = addSectionHeaderConfig($spec, $index);
        $index++;
        continue;
    }
    if ($element[2] === 'Message') {
        $spec = addMessageNoteTextConfig($spec, $index);
        $index++;
        continue;
    }
    switch ($element[2]) {
        case 'Drop Down':
            $spec = addDropDownConfig($spec, $index, $element, $oldValueOptions, $oldValueOptionsOrder);
            break;
        case 'Text Box':
            $spec = addTextBoxConfig($spec, $index, $element);
            break;
        case 'Date Picker': //todo
            $spec = addDatePickerConfig($spec, $index, $element);
            break;
        case 'Radio Button':
            $spec = addRadioConfig($spec, $index, $element, $oldValueOptions, $oldValueOptionsOrder);
            break;
        case 'Checkbox':
            $spec = addCheckBoxConfig($spec, $index, $element, $oldValueOptions, $oldValueOptionsOrder);
            break;
        case 'Text Area':
            $spec = addTextAreaConfig($spec, $index, $element);
    }
    switch ($element[3]) {
        case '(POSTCODE VALIDATION)':
            $spec = addPostCodeConfig($spec, $index);
            break;
        case '(MOBILE PHONE NUMBER VALIDATION)':
            $spec = addMobileConfig($spec, $index, $element);
            break;
        case '(PHONE NUMBER VALIDATION)':
            $spec = addPhoneConfig($spec, $index, $element);
            break;
        case '(EMAIL VALIDATION)':
            $spec = addEmailConfig($spec, $index, $element);
            break;
        case '(PAST DATE VALIDATION)':
            $spec = addPastDateConfig($spec, $index, $element);
            break;
        case '(FUTURE DATE VALIDATION)':
            $spec = addFutureDateConfig($spec, $index, $element);
            break;
        case '(NHS NO. VALIDATION)':
            $spec = addNHSConfig($spec, $index);
            break;
    }
    // todo (500 CHARACTERS) and as per iaptus
    if ($element[5] === 'Yes') {
        $spec = addRequiredConfig($spec, $index, $element);
    }
    if ($element[5] === 'No') {
        $spec = addNotRequiredConfig($spec, $index, $element);
    }
    if (preg_match('/^EITHER (.*) OR BOTH (.*)/i', $element[5], $orMatches)) {
        $spec = addEitherOrConfig($element, $spec, $orMatches, $elementIndex);
    }
    if (preg_match('/^MUST MATCH (.*)/i', $element[5], $mustMatches)) {
        $spec = addMustMatchConfig($element, $spec, $mustMatches, $elementIndex);
    }
    if (preg_match('/^if (.*) = (.*)/i', $element[5], $ifMatches)) {
        if ($ifMatches[2] === 'TRUE') {
            $spec = addIfOtherEqualsTrueConfig($element, $spec, $ifMatches, $elementIndex, $index);
        } else {
            $spec = addIfOtherEqualsValueConfig($element, $ifMatches, $spec, $elementIndex, $index);
        }
    }
    $spec['elements'][$index]['spec']['options']['label'] = $element[1];
    $index++;
}
//var_export($spec);exit;
$form = saveConfig($spec, $title);
if (isset($form['uuid'])) {
    echo '//Saved config for ' . $form['title'] . ' with uuid: ' . $form['uuid'] . PHP_EOL;
} else {
    echo '//Error saving form - title did not match entry in form_config table' . PHP_EOL;
}


function dumpOldSpec($spec, $title)
{
    $export = var_export($spec, true);
    file_put_contents(__DIR__ . '/../data/' . $title . '.php', '<?php 
     return ' . $export . ';');
}

function saveConfig($spec, $title)
{
    $sql = 'UPDATE form_configs
SET spec=:spec
WHERE title=:title;';

    $dbPaywall = new PDO('mysql:host=192.168.20.99;dbname=paywall', 'root', '');
    $stmt = $dbPaywall->prepare($sql);
    $stmt->execute([':spec' => json_encode($spec), ':title' => $title,]);

    $sql = 'SELECT title, uuid FROM form_configs
         WHERE title=:title;';
    $stmt = $dbPaywall->prepare($sql);
    $stmt->execute([':title' => $title,]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
}

function fetchFormConfig($title)
{
    $dbPaywall = new PDO('mysql:host=192.168.20.99;dbname=paywall', 'root', '');
    $sql = 'SELECT spec FROM form_configs
         WHERE title=:title;';
    $stmt = $dbPaywall->prepare($sql);
    $stmt->execute([':title' => $title]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
}

/**
 * Recursively computes the intersection of arrays using keys for comparison.
 *
 * @param   array $array1 The array with master keys to check.
 * @param   array $array2 An array to compare keys against.
 * @return  array associative array containing all the entries of array1 which have keys that are present in array2.
 **/
function array_intersect_key_recursive(array $array1, array $array2)
{
    $array1 = array_intersect_key($array1, $array2);
    foreach ($array1 as $key => &$value) {
        if (is_array($value) && is_array($array2[$key])) {
            $value = array_intersect_key_recursive($value, $array2[$key]);
        }
    }
    return $array1;
}

function array_diff_key_recursive(array $arr1, array $arr2)
{
    $diff = array_diff_key($arr1, $arr2);
    $intersect = array_intersect_key($arr1, $arr2);

    foreach ($intersect as $k => $v) {
        if (is_array($arr1[$k]) && is_array($arr2[$k])) {
            $d = array_diff_key_recursive($arr1[$k], $arr2[$k]);

            if ($d) {
                $diff[$k] = $d;
            }
        }
    }

    return $diff;
}


function same_keys($a1, $a2)
{
    $same = false;
    if (!array_diff_key($a1, $a2)) {
        $same = true;
        foreach ($a1 as $k => $v) {
            if (is_array($v) && !same_keys($v, $a2[$k])) {
                $same = false;
                break;
            }
        }
    }
    return $same;
}