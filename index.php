<?php
require_once 'vendor/autoload.php';
########################################################

## PH GREAT
// require_once 'Executable/PHGreateScraper.php';
// $scraper = new PHGreatScraper();
// $scraper->run(2); // You can increase the number of listings 

#########################################################

## Ideal Homes Portugal
// require_once 'Executable/IdealHomePortugal.php';
// $scraper = new IdealHomePortugal();
// $scraper->run(3);

#########################################################

##  BARRATS HOMES
// require_once 'Executable/BarrattHomes.php';
// $scraper = new BarrattHomes();

// $eastMidlands = [
//     "/new-homes/east-midlands/derbyshire/",
//     "/new-homes/east-midlands/leicestershire/",
//     "/new-homes/east-midlands/lincolnshire/",
//     "/new-homes/east-midlands/northamptonshire/",
//     "/new-homes/east-midlands/nottinghamshire/",
//     "/new-homes/east-midlands/waterbeach/"
// ];

// $filename = "London";
// $london = [
//     "/search-results/?qloc=London%252C%2520UK&latLng=51.5072178%252C-0.1275862"
// ];
// $scraper->run($london, 2, $filename
// ); // Optional limit

#########################################################

## MEXICAN ROOF
//require_once 'Executable/RealEstateScraper.php';
// $scraper = new RealEstateScraper();
// $scraper->run(70);

#########################################################

## BLUESKY HOMES
// require_once 'Executable/BlueskyHouses.php';
// $scraper = new BlueskyHouses();
// $scraper->run(94);

#########################################################

## MRESIDENCE
// require_once 'Executable/MResidence.php';
// $scraper = new MResidence();
// $scraper->run();

#########################################################

## Marbella Realty Group
// require_once 'Executable/MarbellaRealtyGroup.php';
// $scraper = new MarbellaRealtyGroup();
// $scraper->run();

#########################################################

## MYBALI
// require_once 'Executable/MyBali.php';
// $scraper = new MyBali();
// $scraper->run(4);

#########################################################

## DAR GLOBAL
// require_once 'Executable/DarGlobal.php';
// $scraper = new DarGlobal();
// $scraper->run(1);

#########################################################

## AL Sabr
// require_once 'Executable/AlSabr.php';
// $scraper = new AlSabr('src/HTML/AlSabr.html');
// $scraper->run();

#########################################################

## Luxury Estate Turkey
// require_once 'Executable/LuxuryEstateTurkey.php';
// $scraper = new LuxuryEstateTurkey();
// $scraper->run(72);

#########################################################

## Bay Side Real Estate
// require_once 'Executable/BaySideRE.php';
// $scraper = new BaySideRE();
// $scraper->run(19);


#########################################################

## Buy Properties In Turkey
// require_once 'Executable/BuyPropertiesInTurkey.php';
// $scraper = new BuyPropertiesInTurkey();
// $scraper->run(23);

#########################################################

## Ideal Homes Portugal
// require_once 'Executable/IdealHomeInternational.php';
// $scraper = new IdealHomeInternational();
// $scraper->run(53);


#########################################################

## Holiday Homes Spain
// require_once 'Executable/HolidayHomesSpain.php';
// $scraper = new HolidayHomesSpain();
// $scraper->run(763);

#########################################################

## Holiday Homes Spain
// require_once 'Executable/StellarEstateAstraRE.php';
// $scraper = new StellarEstateAstraRE();
// $scraper->run(25);

#########################################################

## JLL
// require_once 'ExecutableXML/JLL.php';
// $scraper = new JLL();
// $scraper->run(); 

#########################################################

// require_once 'ExecutableXML/ThaiEstate.php';
// $scraper = new ThaiEstate();
// $scraper->run('d:\Users\celis\Downloads\Thai_Estate_Property_Feed_20-09-2025.xml');


#########################################################

## Hurghadians Property
// require_once 'Executable/HurghadiansProperty.php';
// $scraper = new HurghadiansProperty();
// $scraper->run(2);


#########################################################

## Hurghadians Property
// require_once 'Executable/HurghadiansProperty.php';
// $scraper = new HurghadiansProperty();
// $scraper->run(15);


// require_once 'ExecutableXML/ThaiEstate.php';
// $scraper = new ThaiEstate();
// $scraper->run('d:\Users\celis\Downloads\Thai_Estate_Property_Feed_20-09-2025.xml');

#########################################################

## KYERO XML FEED

## Nilsott
// require_once 'ExecutableXML/KyeroXML.php';
// $scraper = new KyeroXML();
// ## Nilsott
// $scraper->run('https://web3930:9a42ded9cb@www.nilsott.com/xml/kyero.xml', 0, [
//     'Owned By' => 'Nils Ott Group Ltd.',
//     'Contact Person' => 'Nils Birger Ott ; Milena Krasteva',
//     'Phone' => '+49 172 9535030',
//     'Email' => 'milena@nilsott.com',
//     'listing_id_prefix' => 'NOG-'
// ]);

## Holiday Home
// $scraper->run('https://modernpropertymarbella.com/xml/modernpropertymarbella-thinkspain.xml', 0, [
//     'Owned By' => 'Holiday Homes Spain',
//     'Contact Person' => 'Darren Ashley',
//     'Phone' => '+34 722 43 32 94',
//     'Email' => 'darren@holiday-homes-spain.com',
//     'listing_id_prefix' => 'HS-'
// ]);


#########################################################

## Holiday Homes Spain
// require_once 'Executable/CasaEspanha.php';
// $scraper = new CasaEspanha();
// $scraper->run(2);

#########################################################

// require_once 'ExecutableXML/BlueSkyHousesXML.php';
// $scraper = new BlueSkyHousesXML();
// $scraper->run('https://www.bluesky-houses.com/properties/xml/overseas-agents-ee22cc03ff04fe', 0, [
//     'Owned By' => 'FAE BlueSky Houses Ltd. v2.0',
//     'Contact Person' => 'Elena Davison',
//     'Phone' => '+357 26 938900 | 8000 7020',
//     'Email' => 'info@bluesky-houses.com',
//     'listing_id_prefix' => 'BH'
// ]);`


// require_once 'ExecutableXML/AtCityFind.php';
// $scraper = new AtCityFind();
// $scraper->run('https://atcityfind.com/api/gwNVwd/feeds/nestopa-feed', 0, [
//     'Owned By' => 'AT Cityfind',
//     'Contact Person' => 'Tivanon Ngoysunern | Alex Blencowe',
//     'Phone' => '+66 80 019 9103',
//     'Email' => 'atcityfind1160@gmail.com',
//     'listing_id_prefix' => 'ACF-'
// ]);
