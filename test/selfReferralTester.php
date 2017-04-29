<?php


if ($argv[1] == 'yes') {
    $formParams = unserialize(file_get_contents(__DIR__ . '/../data/Barnet Self Referral Form-Yes-values.php'));
    $sql = 'SELECT
    `title` as `pat_title` ,
    `first_name` as `pat_firstname` ,
    `last_name` as `pat_lastname` ,
    `date_of_birth` as `pat_dob` ,
    `address_1` as `pat_address1` ,
    `address_2` as `pat_address2` ,
    `town` as `pat_town_city` ,
    `county` as `pat_county` ,
    `postcode` as `pat_postcode` ,
    `gender` as `pat_gender` 
    FROM `self_referrals` 
    WHERE `last_name` = \'Yes\' ;';

    $db = new \PDO(
        'mysql:host=127.0.0.1;dbname=iaptus',
        'root',
        ''
    );
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $dbValue = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

    $sql = "SELECT * FROM `self_referrals_custom`";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $customStuff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($customStuff as $item) {
        $dbValue[$item['field_name']] = $item['field_value'];
    }
} else {
    $formParams = unserialize(file_get_contents(__DIR__ . '/../data/Barnet Self Referral Form-No-values.php'));


    $sql = 'SELECT
    `title` as `pat_title` ,
    `first_name` as `pat_firstname` ,
    `last_name` as `pat_lastname` ,
    `date_of_birth` as `pat_dob` ,
    `address_1` as `pat_address1` ,
    `address_2` as `pat_address2` ,
    `town` as `pat_town_city` ,
    `county` as `pat_county` ,
    `postcode` as `pat_postcode` ,
    `gender` as `pat_gender` 
    FROM `self_referrals` 
    WHERE `last_name` = \'No\' ;';

    $db = new \PDO(
        'mysql:host=127.0.0.1;dbname=iaptus',
        'root',
        ''
    );
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $dbValue = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

    $sql = "SELECT * FROM `self_referrals_custom`";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $customStuff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($customStuff as $item) {
        $dbValue[$item['field_name']] = $item['field_value'];
    }
}

$inDBbutNotForm = array_diff_assoc($dbValue, $formParams);
echo '$inDBbutNotForm' . PHP_EOL;
var_export($inDBbutNotForm);

$inFormbutNotDB = array_diff_assoc($formParams, $dbValue);

echo '$inFormbutNotDB' . PHP_EOL;
var_export($inFormbutNotDB);




