<?php
include("../class/config.php");
require(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require(SITE_PATH . "/class/".DB_TYPE.".class.php");
require(SITE_PATH . "/class/language.class.php");
require(SITE_PATH . "/class/text.class.php");
require(SITE_PATH . "/class/templatef.class.php");
require(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicCommon.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;
$sq2 = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$ic = IsicCommon::getInstance();
$appl_list = array(
'5444',
'5445',
'5446',
'5447',
'5448',
'5449',
'5450',
'5451',
'5452',
'5453',
'5454',
'5455',
'5456',
'5457',
'5458',
'5459',
'5460',
'5461',
'5462',
'5463',
'5464',
'5465',
'5466',
'5467',
'5468',
'5469',
'5470',
'5471',
'5472',
'5473',
'5474',
'5475',
'5476',
'5477',
'5478',
'5479',
'5480',
'5481',
'5482',
'5483',
'5484',
'5485',
'5486',
'5487',
'5488',
'5489',
'5490',
'5491',
'5492',
'5493',
'5494',
'5495',
'5496',
'5497',
'5498',
'5499',
'5500',
'5501',
'5502',
'5503',
'5504',
'5505',
'5506',
'5507',
'5508',
'5509',
'5510',
'5511',
'5512',
'5513',
'5514',
'5515',
'5516',
'5517',
'5518',
'5519',
'5520',
'5521',
'5522',
'5523',
'5524',
'5525',
'5526',
'5527',
'5528',
'5529',
'5530',
'5531',
'5532',
'5533',
'5534',
'5535',
'5536',
'5537',
'5538',
'5539',
'5540',
'5541',
'5542',
'5543',
'5544',
'5545',
'5546',
'5547',
'5548',
'5549',
'5550',
'5551',
'5552',
'5553',
'5554',
'5555',
'5556',
'5557',
'5558',
'5559',
'5560',
'5561',
'5562',
'5563',
'5564',
'5565',
'5566',
'5567',
'5568',
'5569',
'5570',
'5571',
'5572',
'5573',
'5574',
'5575',
'5576',
'5577',
'5578',
'5579',
'5580',
'5581',
'5582',
'5583',
'5584',
'5585',
'5586',
'5587',
'5588',
'5589',
'5590',
'5591',
'5592',
'5593',
'5594',
'5595',
'5596',
'5597',
'5598',
'5599',
'5600',
'5601',
'5602',
'5603',
'5604',
'5605',
'5606',
'5607',
'5608',
'5609',
'5610',
'5611',
'5612',
'5613',
'5614',
'5615',
'5616',
'5617',
'5618',
'5619',
'5620',
'5621',
'5622',
'5623',
'5624',
'5625',
'5626',
'5627',
'5628',
'5629',
'5630',
'5631',
'5632',
'5633',
'5634',
'5635',
'5636',
'5637',
'5638',
'5639',
'5640',
'5641',
'5642',
'5643',
'5644',
'5645',
'5646',
'5647',
'5648',
'5649',
'5650',
'5651',
'5652',
'5653',
'5654',
'5655',
'5656',
'5657',
'5658',
'5659',
'5660',
'5661',
'5662',
'5663',
'5664',
'5665',
'5666',
'5667',
'5668',
'5669',
'5670',
'5671',
'5672',
'5673',
'5674',
'5675',
'5676',
'5677',
'5678',
'5679',
'5680',
'5681',
'5682',
'5684',
'5685',
'5686',
'5687',
'5688',
'5689',
'5690',
'5691',
'5692',
'5693',
'5694',
'5695',
'5696',
'5697',
'5698',
'5699',
'5700',
'5701',
'5702',
'5703',
'5704',
'5705',
'5706',
'5707',
'5708',
'5709',
'5710',
'5711',
'5712',
'5713',
'5714',
'5715',
'5716',
'5717',
'5718',
'5719',
'5720',
'5721',
'5722',
'5723',
'5724',
'5725',
'5726',
'5727',
'5728',
'5729',
'5730',
'5731',
'5732',
'5733',
'5734',
'5735',
'5736',
'5737',
'5738',
'5739',
'5740',
'5741',
'5742',
'5743',
'5744',
'5745',
'5746',
'5747',
'5748',
'5749',
'5750',
'5751',
'5752',
'5753',
'5754',
'5755',
'5756',
'5757',
'5758',
'5759',
'5760',
'5761',
'5762',
'5763',
'5764',
'5765',
'5766',
'5767',
'5768',
'5769',
'5770',
'5771',
'5772',
'5773',
'5774',
'5775',
'5776',
'5777',
'5778',
'5779',
'5780',
'5781',
'5782',
'5783',
'5784',
'5785',
'5786',
);

foreach ($appl_list as $appl_id) {
    echo $appl_id . ": " . $ic->deleteApplication($ic->getApplicationRecord($appl_id), $ic->system_user) . "<br>\n";
}