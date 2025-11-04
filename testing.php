<?php

#########################################################

## MYBALI
// require_once 'Executable/AlSabr.php';
// $scraper = new AlSabr('src/HTML/AlSabr.html');
// $scraper->run();
#########################################################

## Ideal Homes Portugal
// require_once 'Executable/IdealHomePortugal.php';
// $scraper = new IdealHomePortugal();
// $scraper->run(1,3);

#########################################################

## Luxury Estate Turkey
// require_once 'Executable/LuxuryEstateTurkey.php';
// $scraper = new LuxuryEstateTurkey();
// $scraper->run();

#########################################################

## Marbella Realty Group
// require_once 'Executable/MarbellaRealtyGroup.php';
// $scraper = new MarbellaRealtyGroup();
// $scraper->run();


// require_once 'ExecutableXML/ThaiEstate.php';
// $scraper = new ThaiEstate();
// $scraper->run('https://web3930:9a42ded9cb@www.nilsott.com/xml/kyero.xml');


## Nilsott
// require_once 'ExecutableXML/KyeroXML.php';
// $scraper = new KyeroXML();
// // $scraper->run('https://web3930:9a42ded9cb@www.nilsott.com/xml/kyero.xml', 0, [
// //     'Owned By' => 'Nils Ott Group Ltd.',
// //     'Contact Person' => 'Nils Birger Ott ; Milena Krasteva',
// //     'Phone' => '+49 172 9535030',
// //     'Email' => 'milena@nilsott.com',
// //     'listing_id_prefix' => 'NOG-'
// // ]);
// $scraper->run('https://modernpropertymarbella.com/xml/modernpropertymarbella-thinkspain.xml', 0, [
//     'Owned By' => 'Holiday Homes Spain',
//     'Contact Person' => 'Darren Ashley',
//     'Phone' => '+34 722 43 32 94',
//     'Email' => 'darren@holiday-homes-spain.com',
//     'listing_id_prefix' => 'HS-'
// ]);

#########################################################

## BaliBound
require_once 'ExecutableXML/BaliBound.php';
$scraper = new BaliBound();
$scraper->setTestingMode(true); // Enable debug output
// Test with first 3 properties
$scraper->run('https://lhwtlojhugazhwttdleh.supabase.co/functions/v1/property-feed', 3, [
    'Owned By' => 'BaliBound Realty',
    'Contact Person' => 'BaliBound Team',
    'Phone' => '',
    'Email' => 'info@baliboundrealty.com',
    'listing_id_prefix' => 'BR-'
]);

