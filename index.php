<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

date_default_timezone_set('UTC');

spl_autoload_register(function ($classname) {
	require ('classes/' . $classname . '.php');
});

$app = new \Slim\App;
$container = $app->getContainer();

$container['logger'] = function($c) {
	$logger = new \Monolog\Logger('my_logger');
	$file_handler = new \Monolog\Handler\StreamHandler('logs/app.log');
	$logger->pushHandler($file_handler);
	return $logger;
};

$app->get('/auth/ajax/check_auth', function ($request, $response, $args) {
	$this->logger->addInfo('check_auth');

	$code = 401;
	$data = array( 'not_authorized' => 1 );
/*	$code = 200;
	$data = array( 'name' => 'Test-User' );*/

	$response = $response->withJson($data, $code);
	return $response;
});

$app->get('/report/new/ajax', function ($request, $response, $args) {
	$this->logger->addInfo('report/new');
	$params = $request->getQueryParams();
	$latitude = $params['latitude'];
	$longitude = $params['longitude'];

/*	$code = 200;
 	$data = array( 'error' => 'Kann halt nicht gefunden werden' );*/
	$code = 200;
	$data = array( 'foo' => 'Foo' );

	$response = $response->withJson($data, $code);
	return $response;
});

$app->get('/report/new/category_extras', function ($request, $response, $args) {
	$this->logger->addInfo('report/new');
	$params = $request->getQueryParams();
	$latitude = $params['latitude'];
	$longitude = $params['longitude'];

	$code = 200;
 	$data = array( 'error' => 'Location not found' );
/*	$code = 200;
	$data = array( 'foo' => 'Foo' );*/

	$response = $response->withJson($data, $code);
	return $response;
});
/*sub 	category_extras_ajax : Path('category_extras') : Args(0) {
    my ( $self, $c ) = @_;

    $c->forward('initialize_report');
    if ( ! $c->forward('determine_location') ) {
        my $body = encode_json({ error => _("Sorry, we could not find that location.") });
        $c->res->content_type('application/json; charset=utf-8');
        $c->res->body($body);
        return 1;
    }
    $c->forward('setup_categories_and_bodies');
    $c->forward('check_for_category');

    my $category = $c->stash->{category} || "";
    my $category_extra = '';
    my $generate;
    if ( $c->stash->{category_extras}->{$category} && @{ $c->stash->{category_extras}->{$category} } >= 1 ) {
        $c->stash->{category_extras} = { $category => $c->stash->{category_extras}->{$category} };
        $generate = 1;
    }
    if ($c->stash->{unresponsive}->{$category}) {
        $generate = 1;
    }
    if ($generate) {
        $c->stash->{report} = { category => $category };
        $category_extra = $c->render_fragment( 'report/new/category_extras.html');
    }

    my $body = encode_json({ category_extra => $category_extra });

    $c->res->content_type('application/json; charset=utf-8');
    $c->res->body($body);
}*/

$app->get('/ajax/lookup_location', function ($request, $response, $args) {
	$this->logger->addInfo('lookup_location');
	$params = $request->getQueryParams();
	$term = $params['term'];

	$code = 200;
	$data = array(
		'latitude' => 52.520645,
		'longitude' => 13.409779,
	);
	$data = array(
		'suggestions' => array( 'Fernsehturm', 'Rotes Rathaus'),
		'locations' => array(
			array('lat' => 52.520645,'long' => 13.409779,'address' => 'Fernsehturm'),
			array('lat' => 52.518611,'long' => 13.408333,'address' => 'Rotes Rathaus')
		),
	);
/*	$data = array(
		'error' => 'Berlin nich jefunden',
	);*/

	$response = $response->withJson($data, $code);
	return $response;
});

$app->get('/ajax', function ($request, $response, $args) {
	$this->logger->addInfo('ajax');
	$data = $request->getQueryParams();
	$bbox = explode(',', $data['bbox']);

	// {"csrfToken":"686775af-a24d-42f6-944d-b341b37d9c8c","rpc":[["0","com.vaadin.shared.ui.ui.UIServerRpc","scroll",[26,0]],["0","com.vaadin.shared.ui.ui.UIServerRpc","resize",[761,940,940,761]],["0","v","v",["location",["s","https://ordnungsamt.berlin.de/frontend/dynamic/#!meldungAktuell"]]]],"syncId":1}
	// {"csrfToken":"686775af-a24d-42f6-944d-b341b37d9c8c","rpc":[["0","com.vaadin.shared.ui.ui.UIServerRpc","scroll",[0,0]],["167","v","v",["firstToBeRendered",["i",0]]],["167","v","v",["lastToBeRendered",["i",45]]],["167","v","v",["reqfirstrow",["i",15]]],["167","v","v",["reqrows",["i",31]]]],"syncId":2}

	$ret = array(
		'pins' => array(),
		'current' => "\n\n    <li class=\"item-list__item item-list__item--empty\">\n        <p>There are no reports to show.</p>\n    </li>\n\n",
	);

	$response->write(json_encode($ret));
	return $response;
});

function fetchRootConfig()
{
	function curl_post( $url, $post)
	{
//		$url_ref = 'https://ordnungsamt.berlin.de';
		$ua = 'Mozilla/5.0 (Windows NT 5.1; rv:16.0) Gecko/20100101 Firefox/16.0 (FixMyStreet.Berlin robot)';
		$cookiefile = __DIR__ . '/cookiefile.txt';
		$headers = array();
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL,            $url);
//		curl_setopt( $ch, CURLOPT_REFERER,        $url_ref);
		curl_setopt( $ch, CURLOPT_USERAGENT,      $ua);
		curl_setopt( $ch, CURLOPT_COOKIEFILE,     $cookiefile);
		curl_setopt( $ch, CURLOPT_COOKIEJAR,      $cookiefile);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt( $ch, CURLOPT_NOBODY,         false);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt( $ch, CURLOPT_HTTPHEADER,     $headers);
		curl_setopt( $ch, CURLOPT_POST,           true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS,     $post);

		$ret = curl_exec( $ch);
		curl_close( $ch);

		return $ret;
	}

	function getBrowserDetailsParametersMobile()
	{
		$params = 'v-sh=' . 900;
		$params .= '&v-sw=' . 1440;
		$params .= '&v-cw=' . 375 . '&v-ch=' . 667;

		$params .= '&v-curdate=' . (time() * 1000);
		$params .= '&v-tzo=-60';
		$params .= '&v-dstd=60';
		$params .= '&v-rtzo=-60';
		$params .= '&v-dston=' . false;

		$params .= '&v-loc=https%3A%2F%2Fordnungsamt.berlin.de%2Ffrontend.mobile%2F';
//		$params .= '&v-wn=' . '1014745576';
		$params += '&v-td=1';

		return $params;
	}

	function getBrowserDetailsParametersDesktop()
	{
		$params = 'v-sh=' . 900;
		$params .= '&v-sw=' . 1440;
		$params .= '&v-cw=' . 940 . '&v-ch=' . 761;

		$params .= '&v-curdate=' . (time() * 1000);
		$params .= '&v-tzo=-120';
		$params .= '&v-dstd=60';
		$params .= '&v-rtzo=-60';
		$params .= '&v-dston=' . 'true';

		$params .= '&v-loc=https%3A%2F%2Fordnungsamt.berlin.de%2Ffrontend%2Fdynamic?redirect-mobile=ignore#!meldungAktuell';
//		$params .= '&v-wn=' . 'frontenddynamic-1141753363-0.17079044482670724';

		return $params;
	}

	function getMobileData()
	{
		$url = 'https://ordnungsamt.berlin.de/frontend.mobile/';
		// Timestamp to avoid caching
		$url .= '?v-' . (time() * 1000);

		$params = 'v-browserDetails=1';
		$params .= '&theme=mobile';
		$params .= '&v-appId=' . 'frontendmobile-1035679436';
		$params .= '&' . getBrowserDetailsParametersMobile();

		return curl_post( $url, $params);
	}

	function getDesktopData()
	{
		$url = 'https://ordnungsamt.berlin.de/frontend/dynamic?redirect-mobile=ignore&v-1471285022940';

		$params = 'v-browserDetails=1';
		$params .= '&theme=frontend';
		$params .= '&v-appId=' . 'frontenddynamic-1141753363';
		$params .= '&' . getBrowserDetailsParametersDesktop();

		return curl_post( $url, $params);
	}

	// VERSION 1: Use the mobile interface

	// vaadin.initApplication(
	// appId = "frontendmobile-1035679436",
	// config = {
	// "theme":"mobile",
	// "versionInfo":{"vaadinVersion":"7.5.2"},
	// "widgetset":"at.techtalk.ams.frontend.mobile.widgetset.AppWidgetSet",
	// "comErrMsg":{"caption":null,"message":null,"url":null},
	// "authErrMsg":{"caption":"Authentication problem","message":"Take note of any unsaved data, and <u>click here</u> or press ESC to continue.","url":null},
	// "sessExpMsg":{"caption":null,"message":null,"url":null},
	// "vaadinDir":"./VAADIN/",
	// "standalone":true,
	// "heartbeatInterval":300,
	// "serviceUrl":".",
	// "widgetsetUrl":"./VAADIN/widgetsets/at.techtalk.ams.frontend.mobile.widgetset.AppWidgetSet/at.techtalk.ams.frontend.mobile.widgetset.AppWidgetSet.nocache.js",
	// "offlineEnabled":true});

	// VERSION 2: Use the desktop interface

/*	"changes" : [
		[
			"change",
			{
				"pid":"847"},
			[
				"0",
				{
					"id":"847",
					"location":"https:\\/\\/ordnungsamt.berlin.de\\/frontend\\/dynamic?redirect-mobile=ignore#!meldungAktuell",
					"focused":"893",
					"v":{
						"action":""}},
				[
					"actions",
					{},
					[
						"action",
						{
							"key":"1",
							"caption":"triggerMeldungAktuell",
							"kc":13,
							"mk":[]}]]]],
		[
			"change",
			{
				"pid":"849"},
			[
				"1",
				{
					"id":"849"}]],
		[
			"change",
			{
				"pid":"857"},
			[
				"2",
				{
					"id":"857"}]],
		[
			"change",
			{
				"pid":"922"},
			[
				"3",
				{
					"id":"922",
					"selectmode":"none",
					"cols":3,
					"rows":15,
					"firstrow":0,
					"totalrows":3968,
					"pagelength":15,
					"colheaders":true,
					"colfooters":false,
					"vcolorder":[
						"1",
						"2",
						"3"],
					"pb-ft":0,
					"pb-l":14,
					"v":{
						"firstvisible":0,
						"firstvisibleonlastpage":-1,
						"sortcolumn":"null",
						"sortascending":true,
						"reqrows":-1,
						"reqfirstrow":-1}},
				[
					"rows",
					{},
					[
						"tr",{
							"key":1,
							"descr-1":"15.08.2016 - 19:55:02",
							"descr-2":"Elektroschrott abgelagert"},
						"15.08.2016 - 19:55 Uhr",
						[
							"4",{
								"id":"1225",
								"cached":true}],
						[
							"5",{
								"id":"1229",
								"cached":true}]],
					[
						"tr",{
							"key":2,
							"descr-1":"15.08.2016 - 17:43:45",
							"descr-2":"Parkraumbewirtschaftung"},
						"15.08.2016 - 17:43 Uhr",
						[
							"4",{
								"id":"1235",
								"cached":true}],
						[
							"5",{
								"id":"1239",
								"cached":true}]],
					[\"tr\",{\"key\":3,\"descr-1\":\"15.08.2016 - 17:36:49\",\"descr-2\":\"Park- und Haltverbot nicht berücksichtigt\"},\"15.08.2016 - 17:36 Uhr\",[\"4\",{\"id\":\"1245\",\"cached\":true}],[\"5\",{\"id\":\"1249\",\"cached\":true}]],[\"tr\",{\"key\":4,\"descr-1\":\"15.08.2016 - 15:53:52\",\"descr-2\":\"Verkehrsbehinderung allgemein\"},\"15.08.2016 - 15:53 Uhr\",[\"4\",{\"id\":\"1255\",\"cached\":true}],[\"5\",{\"id\":\"1259\",\"cached\":true}]],[\"tr\",{\"key\":5,\"descr-1\":\"15.08.2016 - 15:35:50\",\"descr-2\":\"Straßenschäden\"},\"15.08.2016 - 15:35 Uhr\",[\"4\",{\"id\":\"1265\",\"cached\":true}],[\"5\",{\"id\":\"1269\",\"cached\":true}]],[\"tr\",{\"key\":6,\"descr-1\":\"15.08.2016 - 15:33:12\",\"descr-2\":\"Park- und Haltverbot nicht berücksichtigt\"},\"15.08.2016 - 15:33 Uhr\",[\"4\",{\"id\":\"1275\",\"cached\":true}],[\"5\",{\"id\":\"1279\",\"cached\":true}]],[\"tr\",{\"key\":7,\"descr-1\":\"15.08.2016 - 15:32:18\",\"descr-2\":\"Bauabfälle abgelagert\"},\"15.08.2016 - 15:32 Uhr\",[\"4\",{\"id\":\"1285\",\"cached\":true}],[\"5\",{\"id\":\"1289\",\"cached\":true}]],[\"tr\",{\"key\":8,\"descr-1\":\"15.08.2016 - 15:29:32\",\"descr-2\":\"Park- und Haltverbot nicht berücksichtigt\"},\"15.08.2016 - 15:29 Uhr\",[\"4\",{\"id\":\"1295\",\"cached\":true}],[\"5\",{\"id\":\"1299\",\"cached\":true}]],[\"tr\",{\"key\":9,\"descr-1\":\"15.08.2016 - 14:46:07\",\"descr-2\":\"Container\"},\"15.08.2016 - 14:46 Uhr\",[\"4\",{\"id\":\"1305\",\"cached\":true}],[\"5\",{\"id\":\"1309\",\"cached\":true}]],[\"tr\",{\"key\":10,\"descr-1\":\"15.08.2016 - 14:45:57\",\"descr-2\":\"Müllablagerung\"},\"15.08.2016 - 14:45 Uhr\",[\"4\",{\"id\":\"1315\",\"cached\":true}],[\"5\",{\"id\":\"1319\",\"cached\":true}]],[\"tr\",{\"key\":11,\"descr-1\":\"15.08.2016 - 14:15:01\",\"descr-2\":\"Elektroschrott abgelagert\"},\"15.08.2016 - 14:15 Uhr\",[\"4\",{\"id\":\"1325\",\"cached\":true}],[\"5\",{\"id\":\"1329\",\"cached\":true}]],[\"tr\",{\"key\":12,\"descr-1\":\"15.08.2016 - 14:05:46\",\"descr-2\":\"Sperrmüll abgelagert\"},\"15.08.2016 - 14:05 Uhr\",[\"4\",{\"id\":\"1335\",\"cached\":true}],[\"5\",{\"id\":\"1339\",\"cached\":true}]],[\"tr\",{\"key\":13,\"descr-1\":\"15.08.2016 - 14:01:39\",\"descr-2\":\"Bauaufsicht\"},\"15.08.2016 - 14:01 Uhr\",[\"4\",{\"id\":\"1345\",\"cached\":true}],[\"5\",{\"id\":\"1349\",\"cached\":true}]],[\"tr\",{\"key\":14,\"descr-1\":\"15.08.2016 - 13:35:57\",\"descr-2\":\"Elektroschrott abgelagert\"},\"15.08.2016 - 13:35 Uhr\",[\"4\",{\"id\":\"1355\",\"cached\":true}],[\"5\",{\"id\":\"1359\",\"cached\":true}]],[\"tr\",{\"key\":15,\"descr-1\":\"15.08.2016 - 13:30:51\",\"descr-2\":\"Defekte Ampel\"},\"15.08.2016 - 13:30 Uhr\",[\"4\",{\"id\":\"1365\",\"cached\":true}],[\"5\",{\"id\":\"1369\",\"cached\":true}]]],[\"visiblecolumns\",{},[\"column\",{\"cid\":\"1\",\"caption\":\"<span id=\\\"datum\\\" tabindex=0 onkeydown='amsTableHeaderKeyDown(event.keyCode ? event.keyCode : event.which, \\\"datum\\\")' class='ams-table-headerspan' >Meldungsdatum<\\/span>\",\"fcaption\":\"\",\"sortable\":true,\"width\":140}],[\"column\",{\"cid\":\"2\",\"caption\":\"<span id=\\\"betreff\\\" tabindex=0 onkeydown='amsTableHeaderKeyDown(event.keyCode ? event.keyCode : event.which, \\\"betreff\\\")' class='ams-table-headerspan' >Details<\\/span>\",\"fcaption\":\"\",\"sortable\":true,\"width\":620}],[\"column\",{\"cid\":\"3\",\"caption\":\"<span id=\\\"status\\\" tabindex=0 onkeydown='amsTableHeaderKeyDown(event.keyCode ? event.keyCode : event.which, \\\"status\\\")' class='ams-table-headerspan' >status<\\/span>\",\"fcaption\":\"\",\"sortable\":true,\"width\":130}]]]],
		[
			"change",
			{
				"pid":"899"},
			[
				"7",{
					"id":"899",
					"locale":"de_DE",
					"format":"dd.MM.yyyy",
					"strict":true,
					"wn":false,
					"parsable":true,
					"v":{
						"day":-1,
						"month":-1,
						"year":-1}}]],
		[
			"change",
			{
				"pid":"914"},
			[
				"7",{
					"id":"914",
					"locale":"de_DE",
					"format":"dd.MM.yyyy",
					"strict":true,
					"wn":false,
					"parsable":true,
					"v":{
						"day":-1,
						"month":-1,
						"year":-1}}]],
		[
			"change",
			{
				"pid":"895"},
			[
				"6",
				{
					"id":"895"}]],
		[
			"change",
			{
				"pid":"897"},
			[
				"8",{
					"id":"897",
					"fem":"LAZY",
					"fet":500,
					"pagelength":0,
					"filteringmode":"STARTSWITH",
					"totalitems":13,
					"v":{
						"selected":[
							"1"],
						"filter":"",
						"page":0}},
				[
					"options",
					{},
					[
						"so",{
							"caption":"Alle Bezirke",
							"key":"1"}],
					[
						"so",{
							"caption":"Charlottenburg-Wilmersdorf",
							"key":"2"}],
					[
						"so",{
							"caption":"Friedrichshain-Kreuzberg",
							"key":"3"}],
					[\"so\",{\"caption\":\"Lichtenberg\",\"key\":\"4\"}],[\"so\",{\"caption\":\"Marzahn-Hellersdorf\",\"key\":\"5\"}],[\"so\",{\"caption\":\"Mitte\",\"key\":\"6\"}],[\"so\",{\"caption\":\"Neukölln\",\"key\":\"7\"}],[\"so\",{\"caption\":\"Pankow\",\"key\":\"8\"}],[\"so\",{\"caption\":\"Reinickendorf\",\"key\":\"9\"}],[\"so\",{\"caption\":\"Spandau\",\"key\":\"10\"}],[\"so\",{\"caption\":\"Steglitz-Zehlendorf\",\"key\":\"11\"}],[\"so\",{\"caption\":\"Tempelhof-Schöneberg\",\"key\":\"12\"}],[\"so\",{\"caption\":\"Treptow-Köpenick\",\"key\":\"13\"}]]]],
		[
			"change",
			{
				"pid":"893"},
			[
				"6",{
					"id":"893"}]],
		[
			"change",
			{
				"pid":"912"},
			[
				"8",{
					"id":"912",
					"fem":"LAZY",
					"fet":500,
					"nullselect":true,
					"pagelength":0,
					"filteringmode":"STARTSWITH",
					"totalitems":2001,
					"totalMatches":1,
					"v":{
						"selected":[],
						"filter":"",
						"page":0}},
				[
					"options",
					{},
					[
						"so",{
							"caption":"",
							"key":""}],
					[
						"so",{
							"caption":"Aachener Straße [Wilmersdorf]",
							"key":"1"}],
					[
						"so",{
							"caption":"Aalemannsteg [Hakenfelde]",
							"key":"2"}],
					[\"so\",{\"caption\":\"Aalemannufer [Hakenfelde]\",\"key\":\"3\"}],
					[\"so\",{\"caption\":\"Aalesunder Straße [Prenzlauer Berg]\",\"key\":\"4\"}],
					[\"so\",{\"caption\":\"Aalstieg [Rahnsdorf]\",\"key\":\"5\"}],
					[\"so\",{\"caption\":\"Aarauer Straße [Lichterfelde]\",\"key\":\"6\"}],
					[\"so\",{\"caption\":\"Aarberger Straße [Lichterfelde]\",\"key\":\"7\"}],
					[\"so\",{\"caption\":\"Abajstraße [Rosenthal]\",\"key\":\"8\"}],
					[\"so\",{\"caption\":\"Abbestraße [Charlottenburg]\",\"key\":\"9\"}],[\"so\",{\"caption\":\"Abendrotweg [Lichtenrade]\",\"key\":\"10\"}],[\"so\",{\"caption\":\"Ableiterbrücke [Marzahn]\",\"key\":\"11\"}],[\"so\",{\"caption\":\"Abram-Joffe-Straße [Adlershof]\",\"key\":\"12\"}],[\"so\",{\"caption\":\"Abteibrücke(Zug. Insel der Jugend) [Alt-Treptow]\",\"key\":\"13\"}],[\"so\",{\"caption\":\"Abtstraße [Adlershof]\",\"key\":\"14\"}],[\"so\",{\"caption\":\"Abtweilerstraße [Müggelheim]\",\"key\":\"15\"}],[\"so\",{\"caption\":\"Abzugsgrabenbrücke [Haselhorst]\",\"key\":\"16\"}],[\"so\",{\"caption\":\"Achardstraße [Kaulsdorf]\",\"key\":\"17\"}],[\"so\",{\"caption\":\"Achenbachstraße [Spandau]\",\"key\":\"18\"}],[\"so\",{\"caption\":\"Achenseeweg [Lichterfelde]\",\"key\":\"19\"}],[\"so\",{\"caption\":\"Achillesstraße [Karow]\",\"key\":\"20\"}],[\"so\",{\"caption\":\"Achtermannstraße [Pankow]\",\"key\":\"21\"}],[\"so\",{\"caption\":\"Achtrutenberg [Karow]\",\"key\":\"22\"}],[\"so\",{\"caption\":\"Ackerplanweg [Tegel]\",\"key\":\"23\"}],[\"so\",{\"caption\":\"Ackerstraße [Gesundbrunnen, Mitte]\",\"key\":\"24\"}],[\"so\",{\"caption\":\"Ackerstraße [Spandau]\",\"key\":\"25\"}],[\"so\",{\"caption\":\"Adalbertstraße [Kreuzberg, Mitte]\",\"key\":\"26\"}],[\"so\",{\"caption\":\"Adam-Kuckhoff-Platz [Friedenau]\",\"key\":\"27\"}],[\"so\",{\"caption\":\"Adamstraße [Wilhelmstadt]\",\"key\":\"28\"}],[\"so\",{\"caption\":\"Adam-von-Trott-Straße [Charlottenburg-Nord]\",\"key\":\"29\"}],[\"so\",{\"caption\":\"Adele-Sandrock-Straße [Hellersdorf]\",\"key\":\"30\"}],[\"so\",{\"caption\":\"Adele-Schreiber-Krieger-Straße [Mitte]\",\"key\":\"31\"}],[\"so\",{\"caption\":\"Adelheidallee [Tegel]\",\"key\":\"32\"}],[\"so\",{\"caption\":\"Adelheid-Poninska-Straße [Staaken]\",\"key\":\"33\"}],[\"so\",{\"caption\":\"Adenauerplatz [Charlottenburg]\",\"key\":\"34\"}],[\"so\",{\"caption\":\"Adersleber Weg [Marzahn]\",\"key\":\"35\"}],[\"so\",{\"caption\":\"Adickesstraße [Haselhorst]\",\"key\":\"36\"}],[\"so\",{\"caption\":\"Adlerbrücke [Tiergarten]\",\"key\":\"37\"}],[\"so\",{\"caption\":\"Adlergestell [Adlershof, Grünau, Niederschöneweide, Schmöckwitz]\",\"key\":\"38\"}],[\"so\",{\"caption\":\"Adlergestellbrücke [Grünau]\",\"key\":\"39\"}],[\"so\",{\"caption\":\"Adlerhorst [Grünau]\",\"key\":\"40\"}],[\"so\",{\"caption\":\"Adlerplatz [Westend]\",\"key\":\"41\"}],[\"so\",{\"caption\":\"Adlershofer Brücke [Köpenick]\",\"key\":\"42\"}],[\"so\",{\"caption\":\"Adlershofer Straße [Köpenick]\",\"key\":\"43\"}],[\"so\",{\"caption\":\"Adlerstraße [Bohnsdorf]\",\"key\":\"44\"}],[\"so\",{\"caption\":\"Admiralbrücke [Kreuzberg]\",\"key\":\"45\"}],[\"so\",{\"caption\":\"Admiralstraße [Kreuzberg]\",\"key\":\"46\"}],[\"so\",{\"caption\":\"Adolf-Martens-Straße [Lichterfelde]\",\"key\":\"47\"}],[\"so\",{\"caption\":\"Adolf-Menzel-Straße [Kaulsdorf]\",\"key\":\"48\"}],[\"so\",{\"caption\":\"Adolf-Scheidt-Platz [Tempelhof]\",\"key\":\"49\"}],[\"so\",{\"caption\":\"Adolfstraße [Kaulsdorf]\",\"key\":\"50\"}],[\"so\",{\"caption\":\"Adolfstraße [Steglitz]\",\"key\":\"51\"}],[\"so\",{\"caption\":\"Adolfstraße [Wedding]\",\"key\":\"52\"}],[\"so\",{\"caption\":\"Adolfstraße [Zehlendorf]\",\"key\":\"53\"}],[\"so\",{\"caption\":\"Adorfer Straße [Hellersdorf]\",\"key\":\"54\"}],[\"so\",{\"caption\":\"Advokatensteig [Bohnsdorf]\",\"key\":\"55\"}],[\"so\",{\"caption\":\"Aegirstraße [Reinickendorf]\",\"key\":\"56\"}],[\"so\",{\"caption\":\"AEG-Siedlung Heimat [Lübars]\",\"key\":\"57\"}],[\"so\",{\"caption\":\"AEG-Straße [Lübars]\",\"key\":\"58\"}],[\"so\",{\"caption\":\"Affensteinweg [Rosenthal]\",\"key\":\"59\"}],[\"so\",{\"caption\":\"Afrikanische Straße [Wedding]\",\"key\":\"60\"}],[\"so\",{\"caption\":\"Agathe-Lasch-Platz [Halensee]\",\"key\":\"61\"}],[\"so\",{\"caption\":\"Agathenweg [Tegel]\",\"key\":\"62\"}],[\"so\",{\"caption\":\"Agavensteig [Baumschulenweg]\",\"key\":\"63\"}],[\"so\",{\"caption\":\"Agavensteig [Karlshorst]\",\"key\":\"64\"}],[\"so\",{\"caption\":\"Agnes-Hacker-Straße [Altglienicke]\",\"key\":\"65\"}],[\"so\",{\"caption\":\"Agnes-Straub-Weg [Gropiusstadt]\",\"key\":\"66\"}],[\"so\",{\"caption\":\"Agnes-Wabnitz-Straße [Prenzlauer Berg]\",\"key\":\"67\"}],[\"so\",{\"caption\":\"Agnes-Zahn-Harnack-Straße [Moabit]\",\"key\":\"68\"}],[\"so\",{\"caption\":\"Agricolastraße [Moabit]\",\"key\":\"69\"}],[\"so\",{\"caption\":\"Ahlbecker Straße [Prenzlauer Berg]\",\"key\":\"70\"}],[\"so\",{\"caption\":\"Ahlbeerensteig [Staaken]\",\"key\":\"71\"}],[\"so\",{\"caption\":\"Ahlener Weg [Lichterfelde]\",\"key\":\"72\"}],[\"so\",{\"caption\":\"Ahornallee [Blankenburg]\",\"key\":\"73\"}],[\"so\",{\"caption\":\"Ahornallee [Friedrichshagen]\",\"key\":\"74\"}],[\"so\",{\"caption\":\"Ahornallee [Kladow]\",\"key\":\"75\"}],[\"so\",{\"caption\":\"Ahornallee [Köpenick]\",\"key\":\"76\"}],[\"so\",{\"caption\":\"Ahornallee [Mahlsdorf]\",\"key\":\"77\"}],[\"so\",{\"caption\":\"Ahornallee [Rosenthal]\",\"key\":\"78\"}],[\"so\",{\"caption\":\"Ahornallee [Westend]\",\"key\":\"79\"}],[\"so\",{\"caption\":\"Ahornsteig [Tiergarten]\",\"key\":\"80\"}],[\"so\",{\"caption\":\"Ahornstraße [Kaulsdorf]\",\"key\":\"81\"}],[\"so\",{\"caption\":\"Ahornstraße [Rahnsdorf]\",\"key\":\"82\"}],[\"so\",{\"caption\":\"Ahornstraße [Schöneberg]\",\"key\":\"83\"}],[\"so\",{\"caption\":\"Ahornstraße [Steglitz]\",\"key\":\"84\"}],[\"so\",{\"caption\":\"Ahornstraße [Zehlendorf]\",\"key\":\"85\"}],[\"so\",{\"caption\":\"Ahornweg [Friedrichshagen]\",\"key\":\"86\"}],[\"so\",{\"caption\":\"Ahrensdorfer Straße [Marienfelde]\",\"key\":\"87\"}],[\"so\",{\"caption\":\"Ahrensfelder Berge [Marzahn]\",\"key\":\"88\"}],[\"so\",{\"caption\":\"Ahrensfelder Chaussee [Falkenberg, Marzahn]\",\"key\":\"89\"}],[\"so\",{\"caption\":\"Ahrensfelder Platz [Marzahn]\",\"key\":\"90\"}],[\"so\",{\"caption\":\"Ahrenshooper Straße [Neu-Hohenschönhausen]\",\"key\":\"91\"}],[\"so\",{\"caption\":\"Ahrenshooper Zeile [Zehlendorf]\",\"key\":\"92\"}],[\"so\",{\"caption\":\"Ahrweilerstraße [Wilmersdorf]\",\"key\":\"93\"}],[\"so\",{\"caption\":\"Aiblinger Weg [Kladow]\",\"key\":\"94\"}],[\"so\",{\"caption\":\"Aidastraße [Heinersdorf]\",\"key\":\"95\"}],[\"so\",{\"caption\":\"Akademieplatz [Adlershof]\",\"key\":\"96\"}],[\"so\",{\"caption\":\"Akazienallee [Mahlsdorf]\",\"key\":\"97\"}],[\"so\",{\"caption\":\"Akazienallee [Rosenthal]\",\"key\":\"98\"}],[\"so\",{\"caption\":\"Akazienallee [Westend]\",\"key\":\"99\"}],[\"so\",{\"caption\":\"Akazienhof [Bohnsdorf]\",\"key\":\"100\"}],[\"so\",{\"caption\":\"Akazienstraße [Lichterfelde]\",\"key\":\"101\"}],[\"so\",{\"caption\":\"Akazienstraße [Schöneberg]\",\"key\":\"102\"}],[\"so\",{\"caption\":\"Akazienwäldchen [Britz]\",\"key\":\"103\"}],[\"so\",{\"caption\":\"Akazienweg [Hakenfelde]\",\"key\":\"104\"}],[\"so\",{\"caption\":\"Akeleiweg [Johannisthal]\",\"key\":\"105\"}],[\"so\",{\"caption\":\"Akkordeonweg [Französisch Buchholz]\",\"key\":\"106\"}],[\"so\",{\"caption\":\"Alarichplatz [Tempelhof]\",\"key\":\"107\"}],[\"so\",{\"caption\":\"Alarichstraße [Tempelhof]\",\"key\":\"108\"}],[\"so\",{\"caption\":\"Albanstraße [Marienfelde]\",\"key\":\"109\"}],[\"so\",{\"caption\":\"Alberichstraße [Biesdorf]\",\"key\":\"110\"}],[\"so\",{\"caption\":\"Albersweilerweg [Buckow]\",\"key\":\"111\"}],[\"so\",{\"caption\":\"Albert-Einstein-Straße [Adlershof]\",\"key\":\"112\"}],[\"so\",{\"caption\":\"Albert-Hößler-Straße [Lichtenberg]\",\"key\":\"113\"}],[\"so\",{\"caption\":\"Albertinenstraße [Weißensee]\",\"key\":\"114\"}],[\"so\",{\"caption\":\"Albertinenstraße [Zehlendorf]\",\"key\":\"115\"}],[\"so\",{\"caption\":\"Albert-Kuntz-Straße [Hellersdorf]\",\"key\":\"116\"}],[\"so\",{\"caption\":\"Albert-Schweitzer-Platz [Neukölln]\",\"key\":\"117\"}],[\"so\",{\"caption\":\"Albert-Schweitzer-Straße [Friedrichshagen]\",\"key\":\"118\"}],[\"so\",{\"caption\":\"Albertstraße [Schöneberg]\",\"key\":\"119\"}],[\"so\",{\"caption\":\"Albestraße [Friedenau]\",\"key\":\"120\"}],[\"so\",{\"caption\":\"Albiger Weg [Nikolassee]\",\"key\":\"121\"}],[\"so\",{\"caption\":\"Albineaplatz [Johannisthal]\",\"key\":\"122\"}],[\"so\",{\"caption\":\"Alboinplatz [Schöneberg, Tempelhof]\",\"key\":\"123\"}],[\"so\",{\"caption\":\"Alboinstraße [Schöneberg, Tempelhof]\",\"key\":\"124\"}],[\"so\",{\"caption\":\"Albrecht-Achilles-Straße [Wilmersdorf]\",\"key\":\"125\"}],[\"so\",{\"caption\":\"Albrecht-Berblinger-Straße [Kladow]\",\"key\":\"126\"}],[\"so\",{\"caption\":\"Albrecht-Dürer-Straße [Mahlsdorf]\",\"key\":\"127\"}],[\"so\",{\"caption\":\"Albrechtshofer Weg [Staaken]\",\"key\":\"128\"}],[\"so\",{\"caption\":\"Albrechts Teerofen [Wannsee]\",\"key\":\"129\"}],[\"so\",{\"caption\":\"Albrechtstraße [Mitte]\",\"key\":\"130\"}],[\"so\",{\"caption\":\"Albrechtstraße [Steglitz]\",\"key\":\"131\"}],[\"so\",{\"caption\":\"Albrechtstraße [Tempelhof]\",\"key\":\"132\"}],[\"so\",{\"caption\":\"Albrecht-Thaer-Weg [Dahlem]\",\"key\":\"133\"}],[\"so\",{\"caption\":\"Albtalpark Nsg [Waidmannslust]\",\"key\":\"134\"}],[\"so\",{\"caption\":\"Albtalweg [Waidmannslust]\",\"key\":\"135\"}],[\"so\",{\"caption\":\"Albulaweg [Mariendorf]\",\"key\":\"136\"}],[\"so\",{\"caption\":\"Alemannenallee [Westend]\",\"key\":\"137\"}],[\"so\",{\"caption\":\"Alemannenbrücke [Nikolassee]\",\"key\":\"138\"}],[\"so\",{\"caption\":\"Alemannenstraße [Altglienicke]\",\"key\":\"139\"}],[\"so\",{\"caption\":\"Alemannenstraße [Frohnau]\",\"key\":\"140\"}],[\"so\",{\"caption\":\"Alemannenstraße [Nikolassee]\",\"key\":\"141\"}],[\"so\",{\"caption\":\"Alexander-Meißner-Straße [Bohnsdorf]\",\"key\":\"142\"}],[\"so\",{\"caption\":\"Alexanderplatz [Mitte]\",\"key\":\"143\"}],[\"so\",{\"caption\":\"Alexanderstraße [Mitte]\",\"key\":\"144\"}],[\"so\",{\"caption\":\"Alexanderufer [Mitte]\",\"key\":\"145\"}],[\"so\",{\"caption\":\"Alexander-von-Humboldt-Weg [Adlershof]\",\"key\":\"146\"}],[\"so\",{\"caption\":\"Alexandrinenstraße [Kreuzberg, Mitte]\",\"key\":\"147\"}],[\"so\",{\"caption\":\"Alex-Wedding-Straße [Mitte]\",\"key\":\"148\"}],[\"so\",{\"caption\":\"Alfelder Straße [Biesdorf]\",\"key\":\"149\"}],[\"so\",{\"caption\":\"Alfons-Loewe-Straße [Staaken]\",\"key\":\"150\"}],[\"so\",{\"caption\":\"Alfonsstraße [Altglienicke]\",\"key\":\"151\"}],[\"so\",{\"caption\":\"Alfred-Balen-Weg [Wilhelmstadt]\",\"key\":\"152\"}],[\"so\",{\"caption\":\"Alfred-Döblin-Platz [Kreuzberg]\",\"key\":\"153\"}],[\"so\",{\"caption\":\"Alfred-Döblin-Straße [Marzahn]\",\"key\":\"154\"}],[\"so\",{\"caption\":\"Alfred-Grenander-Platz [Zehlendorf]\",\"key\":\"155\"}],[\"so\",{\"caption\":\"Alfred-Jung-Straße [Fennpfuhl, Lichtenberg]\",\"key\":\"156\"}],[\"so\",{\"caption\":\"Alfred-Kowalke-Straße [Friedrichsfelde]\",\"key\":\"157\"}],[\"so\",{\"caption\":\"Alfred-Lion-Steg [Schöneberg]\",\"key\":\"158\"}],[\"so\",{\"caption\":\"Alfred-Randt-Straße [Köpenick]\",\"key\":\"159\"}],[\"so\",{\"caption\":\"Alfred-Rojek-Weg [Rudow]\",\"key\":\"160\"}],[\"so\",{\"caption\":\"Alfred-Scholz-Platz [Neukölln]\",\"key\":\"161\"}],[\"so\",{\"caption\":\"Alfred-Siggel-Weg [Karlshorst]\",\"key\":\"162\"}],[\"so\",{\"caption\":\"Alfredstraße [Lichtenberg]\",\"key\":\"163\"}],[\"so\",{\"caption\":\"Alice-Archenhold-Weg [Niederschöneweide]\",\"key\":\"164\"}],[\"so\",{\"caption\":\"Alice-Berend-Straße [Moabit]\",\"key\":\"165\"}],[\"so\",{\"caption\":\"Alice-Herz-Platz [Mahlsdorf]\",\"key\":\"166\"}],[\"so\",{\"caption\":\"Alice-Salomon-Platz [Hellersdorf]\",\"key\":\"167\"}],[\"so\",{\"caption\":\"Alice-und-Hella-Hirsch-Ring [Rummelsburg]\",\"key\":\"168\"}],[\"so\",{\"caption\":\"Alkenweg [Grünau]\",\"key\":\"169\"}],[\"so\",{\"caption\":\"Allee der Kosmonauten [Lichtenberg, Biesdorf, Marzahn]\",\"key\":\"170\"}],[\"so\",{\"caption\":\"Allée du Stade [Wedding]\",\"key\":\"171\"}],[\"so\",{\"caption\":\"Allée St. Exupéry [Tegel]\",\"key\":\"172\"}],[\"so\",{\"caption\":\"Allendeweg [Köpenick]\",\"key\":\"173\"}],[\"so\",{\"caption\":\"Allendorfer Weg [Alt-Hohenschönhausen]\",\"key\":\"174\"}],[\"so\",{\"caption\":\"Allerstraße [Neukölln]\",\"key\":\"175\"}],[\"so\",{\"caption\":\"Allgäuer Weg [Mariendorf]\",\"key\":\"176\"}],[\"so\",{\"caption\":\"Allmendeweg [Tegel]\",\"key\":\"177\"}],[\"so\",{\"caption\":\"Allmersweg [Johannisthal]\",\"key\":\"178\"}],[\"so\",{\"caption\":\"Almazeile [Konradshöhe]\",\"key\":\"179\"}],[\"so\",{\"caption\":\"Almstadtstraße [Mitte]\",\"key\":\"180\"}],[\"so\",{\"caption\":\"Almutstraße [Hermsdorf]\",\"key\":\"181\"}],[\"so\",{\"caption\":\"Almweg [Mariendorf]\",\"key\":\"182\"}],[\"so\",{\"caption\":\"Alpenrosenweg [Baumschulenweg]\",\"key\":\"183\"}],[\"so\",{\"caption\":\"Alpenveilchenweg [Biesdorf]\",\"key\":\"184\"}],[\"so\",{\"caption\":\"Alpnacher Weg [Heinersdorf]\",\"key\":\"185\"}],[\"so\",{\"caption\":\"Alsaceweg [Blankenfelde]\",\"key\":\"186\"}],[\"so\",{\"caption\":\"Alsbacher Weg [Zehlendorf]\",\"key\":\"187\"}],[\"so\",{\"caption\":\"Alsenbrücke [Wannsee]\",\"key\":\"188\"}],[\"so\",{\"caption\":\"Alsenstraße [Steglitz]\",\"key\":\"189\"}],[\"so\",{\"caption\":\"Alsenstraße [Wannsee]\",\"key\":\"190\"}],[\"so\",{\"caption\":\"Alsenzer Weg [Müggelheim]\",\"key\":\"191\"}],[\"so\",{\"caption\":\"Alsheimer Straße [Lankwitz]\",\"key\":\"192\"}],[\"so\",{\"caption\":\"Alsterweg [Lichterfelde]\",\"key\":\"193\"}],[\"so\",{\"caption\":\"Altarsteinweg [Rosenthal]\",\"key\":\"194\"}],[\"so\",{\"caption\":\"Alt-Biesdorf [Biesdorf, Marzahn]\",\"key\":\"195\"}],[\"so\",{\"caption\":\"Alt-Blankenburg [Blankenburg]\",\"key\":\"196\"}],[\"so\",{\"caption\":\"Alt-Britz [Britz]\",\"key\":\"197\"}],[\"so\",{\"caption\":\"Alt-Buch [Buch]\",\"key\":\"198\"}],[\"so\",{\"caption\":\"Alt-Buckow [Buckow]\",\"key\":\"199\"}],[\"so\",{\"caption\":\"Altdammer Weg [Heiligensee]\",\"key\":\"200\"}],[\"so\",{\"caption\":\"Altdorfer Straße [Lichterfelde]\",\"key\":\"201\"}],[\"so\",{\"caption\":\"Alte Allee [Westend]\",\"key\":\"202\"}],[\"so\",{\"caption\":\"Alte Brauerei [Kreuzberg]\",\"key\":\"203\"}],[\"so\",{\"caption\":\"Alte Hellersdorfer Straße [Hellersdorf]\",\"key\":\"204\"}],[\"so\",{\"caption\":\"Alte Jakobstraße [Kreuzberg, Mitte]\",\"key\":\"205\"}],[\"so\",{\"caption\":\"Alte Kaulsdorfer Straße [Köpenick]\",\"key\":\"206\"}],[\"so\",{\"caption\":\"Alte Kiesgrube Kladow [Kladow]\",\"key\":\"207\"}],[\"so\",{\"caption\":\"Alte Leipziger Straße [Mitte]\",\"key\":\"208\"}],[\"so\",{\"caption\":\"Altenauer Weg [Lichterfelde]\",\"key\":\"209\"}],[\"so\",{\"caption\":\"Altenberger Weg [Niederschönhausen]\",\"key\":\"210\"}],[\"so\",{\"caption\":\"Altenbraker Straße [Neukölln]\",\"key\":\"211\"}],[\"so\",{\"caption\":\"Altenburger Allee [Westend]\",\"key\":\"212\"}],[\"so\",{\"caption\":\"Altenburger Straße [Lankwitz]\",\"key\":\"213\"}],[\"so\",{\"caption\":\"Altenescher Weg [Prenzlauer Berg]\",\"key\":\"214\"}],[\"so\",{\"caption\":\"Altenhofer Straße [Alt-Hohenschönhausen]\",\"key\":\"215\"}],[\"so\",{\"caption\":\"Altenhofer Weg [Borsigwalde, Tegel]\",\"key\":\"216\"}],[\"so\",{\"caption\":\"Altensteinstraße [Dahlem, Lichterfelde]\",\"key\":\"217\"}],[\"so\",{\"caption\":\"Altentreptower Straße [Biesdorf, Hellersdorf]\",\"key\":\"218\"}],[\"so\",{\"caption\":\"Alte Potsdamer Straße [Tiergarten]\",\"key\":\"219\"}],[\"so\",{\"caption\":\"Alter Bernauer Heerweg [Lübars]\",\"key\":\"220\"}],[\"so\",{\"caption\":\"Alter Fischerweg [Rahnsdorf]\",\"key\":\"221\"}],[\"so\",{\"caption\":\"Alter Försterweg [Rahnsdorf]\",\"key\":\"222\"}],[\"so\",{\"caption\":\"Alte Rhinstraße [Marzahn]\",\"key\":\"223\"}],[\"so\",{\"caption\":\"Alter Markt [Köpenick]\",\"key\":\"224\"}],[\"so\",{\"caption\":\"Alter Park Tempelhof [Tempelhof]\",\"key\":\"225\"}],[\"so\",{\"caption\":\"Alter Radelander Weg [Grünau, Schmöckwitz]\",\"key\":\"226\"}],[\"so\",{\"caption\":\"Alter Schönefelder Weg [Altglienicke]\",\"key\":\"227\"}],[\"so\",{\"caption\":\"Alter Segelfliegerdamm [Johannisthal]\",\"key\":\"228\"}],[\"so\",{\"caption\":\"Alter Wiesenweg [Tegel]\",\"key\":\"229\"}],[\"so\",{\"caption\":\"Alte Schönhauser Straße [Mitte]\",\"key\":\"230\"}],[\"so\",{\"caption\":\"Altes Gaswerk Mariendorf [Mariendorf]\",\"key\":\"231\"}],[\"so\",{\"caption\":\"Alt-Friedrichsfelde [Friedrichsfelde, Biesdorf, Marzahn]\",\"key\":\"232\"}],[\"so\",{\"caption\":\"Alt-Gatow [Gatow]\",\"key\":\"233\"}],[\"so\",{\"caption\":\"Altglienicker Brücke (Teltowkanal) [Adlershof]\",\"key\":\"234\"}],[\"so\",{\"caption\":\"Altglienicker Grund [Altglienicke]\",\"key\":\"235\"}],[\"so\",{\"caption\":\"Altgrabauer Straße [Köpenick]\",\"key\":\"236\"}],[\"so\",{\"caption\":\"Althansweg [Marzahn]\",\"key\":\"237\"}],[\"so\",{\"caption\":\"Altheider Straße [Adlershof]\",\"key\":\"238\"}],[\"so\",{\"caption\":\"Alt-Heiligensee [Heiligensee]\",\"key\":\"239\"}],[\"so\",{\"caption\":\"Alt-Hellersdorf [Hellersdorf]\",\"key\":\"240\"}],[\"so\",{\"caption\":\"Alt-Hermsdorf [Hermsdorf]\",\"key\":\"241\"}],[\"so\",{\"caption\":\"Althoffplatz [Steglitz]\",\"key\":\"242\"}],[\"so\",{\"caption\":\"Althoffstraße [Steglitz]\",\"key\":\"243\"}],[\"so\",{\"caption\":\"Altkanzlerstraße [Zehlendorf]\",\"key\":\"244\"}],[\"so\",{\"caption\":\"Altkanzlerstraßenbrücke [Zehlendorf]\",\"key\":\"245\"}],[\"so\",{\"caption\":\"Alt-Karow [Karow]\",\"key\":\"246\"}],[\"so\",{\"caption\":\"Alt-Kaulsdorf [Kaulsdorf]\",\"key\":\"247\"}],[\"so\",{\"caption\":\"Altkircher Straße [Dahlem]\",\"key\":\"248\"}],[\"so\",{\"caption\":\"Alt-Kladow [Kladow]\",\"key\":\"249\"}],[\"so\",{\"caption\":\"Alt-Köpenick [Köpenick]\",\"key\":\"250\"}],[\"so\",{\"caption\":\"Altlandsberger Platz [Marzahn]\",\"key\":\"251\"}],[\"so\",{\"caption\":\"Alt-Lankwitz [Lankwitz]\",\"key\":\"252\"}],[\"so\",{\"caption\":\"Alt-Lichtenrade [Lichtenrade]\",\"key\":\"253\"}],[\"so\",{\"caption\":\"Alt-Lietzow [Charlottenburg]\",\"key\":\"254\"}],[\"so\",{\"caption\":\"Alt-Lübars [Lübars]\",\"key\":\"255\"}],[\"so\",{\"caption\":\"Alt-Mahlsdorf [Mahlsdorf]\",\"key\":\"256\"}],[\"so\",{\"caption\":\"Alt-Mariendorf [Mariendorf]\",\"key\":\"257\"}],[\"so\",{\"caption\":\"Alt-Marienfelde [Marienfelde]\",\"key\":\"258\"}],[\"so\",{\"caption\":\"Altmarkstraße [Steglitz]\",\"key\":\"259\"}],[\"so\",{\"caption\":\"Alt-Marzahn [Marzahn]\",\"key\":\"260\"}],[\"so\",{\"caption\":\"Alt-Moabit [Moabit]\",\"key\":\"261\"}],[\"so\",{\"caption\":\"Alt-Moabiter-Brücke [Moabit]\",\"key\":\"262\"}],[\"so\",{\"caption\":\"Alt-Müggelheim [Müggelheim]\",\"key\":\"263\"}],[\"so\",{\"caption\":\"Altonaer Straße [Hansaviertel, Tiergarten]\",\"key\":\"264\"}],[\"so\",{\"caption\":\"Altonaer Straße [Mahlsdorf]\",\"key\":\"265\"}],[\"so\",{\"caption\":\"Altonaer Straße [Spandau]\",\"key\":\"266\"}],[\"so\",{\"caption\":\"Alt-Pichelsdorf [Wilhelmstadt]\",\"key\":\"267\"}],[\"so\",{\"caption\":\"Altrader Weg [Rudow]\",\"key\":\"268\"}],[\"so\",{\"caption\":\"Alt-Reinickendorf [Reinickendorf]\",\"key\":\"269\"}],[\"so\",{\"caption\":\"Alt-Rudow [Rudow]\",\"key\":\"270\"}],[\"so\",{\"caption\":\"Alt-Schmöckwitz [Schmöckwitz]\",\"key\":\"271\"}],[\"so\",{\"caption\":\"Alt-Schönow [Zehlendorf]\",\"key\":\"272\"}],[\"so\",{\"caption\":\"Altstädter Ring [Spandau]\",\"key\":\"273\"}],[\"so\",{\"caption\":\"Alt-Stralau [Friedrichshain]\",\"key\":\"274\"}],[\"so\",{\"caption\":\"Alt-Tegel [Tegel]\",\"key\":\"275\"}],[\"so\",{\"caption\":\"Alt-Tempelhof [Tempelhof]\",\"key\":\"276\"}],[\"so\",{\"caption\":\"Alt-Treptow [Alt-Treptow]\",\"key\":\"277\"}],[\"so\",{\"caption\":\"Altvaterstraße [Nikolassee]\",\"key\":\"278\"}],[\"so\",{\"caption\":\"Alt-Wittenau [Wittenau]\",\"key\":\"279\"}],[\"so\",{\"caption\":\"Alvenslebenplatz [Lichtenrade]\",\"key\":\"280\"}],[\"so\",{\"caption\":\"Alvenslebenstraße [Lichtenrade]\",\"key\":\"281\"}],[\"so\",{\"caption\":\"Alvenslebenstraße [Schöneberg]\",\"key\":\"282\"}],[\"so\",{\"caption\":\"Alwineweg [Biesdorf]\",\"key\":\"283\"}],[\"so\",{\"caption\":\"Alzeyweg [Lichtenberg]\",\"key\":\"284\"}],[\"so\",{\"caption\":\"Am Adlergestell [Adlershof]\",\"key\":\"285\"}],[\"so\",{\"caption\":\"Am Ahornweg [Wartenberg]\",\"key\":\"286\"}],[\"so\",{\"caption\":\"Am Akazienweg [Wartenberg]\",\"key\":\"287\"}],[\"so\",{\"caption\":\"Amalienhofstraße [Wilhelmstadt]\",\"key\":\"288\"}],[\"so\",{\"caption\":\"Amalienpark [Pankow]\",\"key\":\"289\"}],[\"so\",{\"caption\":\"Amalienstraße [Lankwitz]\",\"key\":\"290\"}],[\"so\",{\"caption\":\"Amalienstraße [Weißensee]\",\"key\":\"291\"}],[\"so\",{\"caption\":\"Am Alten Fenn [Johannisthal]\",\"key\":\"292\"}],[\"so\",{\"caption\":\"Am Alten Friedhof [Altglienicke]\",\"key\":\"293\"}],[\"so\",{\"caption\":\"Am alten Gaswerk [Staaken]\",\"key\":\"294\"}],[\"so\",{\"caption\":\"Am Alten Lokschuppen [Rummelsburg]\",\"key\":\"295\"}],[\"so\",{\"caption\":\"Am Amtsgraben [Köpenick]\",\"key\":\"296\"}],[\"so\",{\"caption\":\"Amandastraße [Hermsdorf]\",\"key\":\"297\"}],[\"so\",{\"caption\":\"Am Anger [Dahlem]\",\"key\":\"298\"}],[\"so\",{\"caption\":\"Amanlisweg [Marzahn]\",\"key\":\"299\"}],[\"so\",{\"caption\":\"Am Ansitz [Waidmannslust]\",\"key\":\"300\"}],[\"so\",{\"caption\":\"Am Appelhorst [Buckow]\",\"key\":\"301\"}],[\"so\",{\"caption\":\"Am Ausblick [Frohnau]\",\"key\":\"302\"}],[\"so\",{\"caption\":\"Ambacher Straße [Köpenick]\",\"key\":\"303\"}],[\"so\",{\"caption\":\"Am Bachrain [Kaulsdorf]\",\"key\":\"304\"}],[\"so\",{\"caption\":\"Am Bahndamm [Köpenick]\",\"key\":\"305\"}],[\"so\",{\"caption\":\"Am Bahnhof Jungfernheide [Charlottenburg-Nord]\",\"key\":\"306\"}],[\"so\",{\"caption\":\"Am Bahnhof Spandau [Spandau]\",\"key\":\"307\"}],[\"so\",{\"caption\":\"Am Bahnhof Westend [Charlottenburg]\",\"key\":\"308\"}],[\"so\",{\"caption\":\"Am Bahnhof Wuhlheide [Köpenick, Oberschöneweide]\",\"key\":\"309\"}],[\"so\",{\"caption\":\"Am Bäkequell [Steglitz]\",\"key\":\"310\"}],[\"so\",{\"caption\":\"Am Baltenring [Hellersdorf, Kaulsdorf]\",\"key\":\"311\"}],[\"so\",{\"caption\":\"Am Bärensprung [Heiligensee]\",\"key\":\"312\"}],[\"so\",{\"caption\":\"Am Barnim [Mahlsdorf]\",\"key\":\"313\"}],[\"so\",{\"caption\":\"Am Bauernwäldchen [Müggelheim]\",\"key\":\"314\"}],[\"so\",{\"caption\":\"Am Bauersee [Müggelheim]\",\"key\":\"315\"}],[\"so\",{\"caption\":\"Am Beelitzhof [Nikolassee]\",\"key\":\"316\"}],[\"so\",{\"caption\":\"Am Berg [Köpenick]\",\"key\":\"317\"}],[\"so\",{\"caption\":\"Am Berghang [Gatow]\",\"key\":\"318\"}],[\"so\",{\"caption\":\"Am Bergpfuhl [Britz]\",\"key\":\"319\"}],[\"so\",{\"caption\":\"Am Berl [Neu-Hohenschönhausen]\",\"key\":\"320\"}],[\"so\",{\"caption\":\"Am Berlin Museum [Kreuzberg]\",\"key\":\"321\"}],[\"so\",{\"caption\":\"Am Biberbau [Frohnau]\",\"key\":\"322\"}],[\"so\",{\"caption\":\"Am Binsengrund [Biesdorf]\",\"key\":\"323\"}],[\"so\",{\"caption\":\"Am Birkenhügel [Wannsee]\",\"key\":\"324\"}],[\"so\",{\"caption\":\"Am Birkenknick [Zehlendorf]\",\"key\":\"325\"}],[\"so\",{\"caption\":\"Am Birkenrevier [Karlshorst]\",\"key\":\"326\"}],[\"so\",{\"caption\":\"Am Birkenwerder [Kaulsdorf]\",\"key\":\"327\"}],[\"so\",{\"caption\":\"Am Bogen [Falkenhagener Feld]\",\"key\":\"328\"}],[\"so\",{\"caption\":\"Am Bootshaus [Hakenfelde]\",\"key\":\"329\"}],[\"so\",{\"caption\":\"Am Borsigturm [Tegel]\",\"key\":\"330\"}],[\"so\",{\"caption\":\"Amboßweg [Wittenau]\",\"key\":\"331\"}],[\"so\",{\"caption\":\"Am Böttcherberg [Wannsee]\",\"key\":\"332\"}],[\"so\",{\"caption\":\"Am Brandpfuhl [Britz]\",\"key\":\"333\"}],[\"so\",{\"caption\":\"Am Breiten Luch [Neu-Hohenschönhausen]\",\"key\":\"334\"}],[\"so\",{\"caption\":\"Am Bremsenwerk [Rummelsburg]\",\"key\":\"335\"}],[\"so\",{\"caption\":\"Am Brendegraben [Französisch Buchholz]\",\"key\":\"336\"}],[\"so\",{\"caption\":\"Am Britzer Garten [Britz]\",\"key\":\"337\"}],[\"so\",{\"caption\":\"Am Brodersengarten [Biesdorf]\",\"key\":\"338\"}],[\"so\",{\"caption\":\"Am Bruchland [Altglienicke]\",\"key\":\"339\"}],[\"so\",{\"caption\":\"Am Brunnen [Tegel]\",\"key\":\"340\"}],[\"so\",{\"caption\":\"Am Buchenberg [Hermsdorf]\",\"key\":\"341\"}],[\"so\",{\"caption\":\"Am Buddeplatz [Tegel]\",\"key\":\"342\"}],[\"so\",{\"caption\":\"Am Bürgerpark [Niederschönhausen, Pankow, Reinickendorf]\",\"key\":\"343\"}],[\"so\",{\"caption\":\"Am Buschfeld [Buckow]\",\"key\":\"344\"}],[\"so\",{\"caption\":\"Am Carlsgarten [Karlshorst]\",\"key\":\"345\"}],[\"so\",{\"caption\":\"Am Cleantech Business Park [Marzahn]\",\"key\":\"346\"}],[\"so\",{\"caption\":\"Am Comeniusplatz [Friedrichshain]\",\"key\":\"347\"}],[\"so\",{\"caption\":\"Am Containerbahnhof [Friedrichshain]\",\"key\":\"348\"}],[\"so\",{\"caption\":\"Am Dachsbau [Heiligensee]\",\"key\":\"349\"}],[\"so\",{\"caption\":\"Am Damm [Friedrichshagen]\",\"key\":\"350\"}],[\"so\",{\"caption\":\"Am Danewend [Karow]\",\"key\":\"351\"}],[\"so\",{\"caption\":\"Am Dianaplatz [Waidmannslust]\",\"key\":\"352\"}],[\"so\",{\"caption\":\"Am Doggelhof [Reinickendorf]\",\"key\":\"353\"}],[\"so\",{\"caption\":\"Am Dominikusteich [Hermsdorf]\",\"key\":\"354\"}],[\"so\",{\"caption\":\"Am Donnerberg [Kladow]\",\"key\":\"355\"}],[\"so\",{\"caption\":\"Am Dorfanger [Wittenau]\",\"key\":\"356\"}],[\"so\",{\"caption\":\"Am Dörferweg [Falkenberg]\",\"key\":\"357\"}],[\"so\",{\"caption\":\"Am Dorfteich [Heiligensee]\",\"key\":\"358\"}],[\"so\",{\"caption\":\"Am Dorfwald [Kladow]\",\"key\":\"359\"}],[\"so\",{\"caption\":\"Am Dornbusch [Westend]\",\"key\":\"360\"}],[\"so\",{\"caption\":\"Am Eichenhain [Frohnau]\",\"key\":\"361\"}],[\"so\",{\"caption\":\"Am Eichenquast [Buckow]\",\"key\":\"362\"}],[\"so\",{\"caption\":\"Am Eichgarten [Steglitz]\",\"key\":\"363\"}],[\"so\",{\"caption\":\"Ameisenweg [Falkenhagener Feld]\",\"key\":\"364\"}],[\"so\",{\"caption\":\"Amelia-Earhart-Straße [Kladow]\",\"key\":\"365\"}],[\"so\",{\"caption\":\"Amelie-Beese-Zeile [Kladow]\",\"key\":\"366\"}],[\"so\",{\"caption\":\"Amelieweg [Kaulsdorf]\",\"key\":\"367\"}],[\"so\",{\"caption\":\"Am Elsebrocken [Karow]\",\"key\":\"368\"}],[\"so\",{\"caption\":\"Am Elsenbruch [Lankwitz]\",\"key\":\"369\"}],[\"so\",{\"caption\":\"Amendestraße [Reinickendorf]\",\"key\":\"370\"}],[\"so\",{\"caption\":\"Am Erlenbusch [Dahlem]\",\"key\":\"371\"}],[\"so\",{\"caption\":\"Am Ernst-Grube-Park [Köpenick]\",\"key\":\"372\"}],[\"so\",{\"caption\":\"Am Espenpfuhl [Rudow]\",\"key\":\"373\"}],[\"so\",{\"caption\":\"Am Eulenhorst [Konradshöhe]\",\"key\":\"374\"}],[\"so\",{\"caption\":\"Am Falkenberg [Altglienicke, Bohnsdorf]\",\"key\":\"375\"}],[\"so\",{\"caption\":\"Am Falkenberg Wasserwerk [Altglienicke]\",\"key\":\"376\"}],[\"so\",{\"caption\":\"Am Falkplatz [Prenzlauer Berg]\",\"key\":\"377\"}],[\"so\",{\"caption\":\"Am Faulen See [Alt-Hohenschönhausen]\",\"key\":\"378\"}],[\"so\",{\"caption\":\"Am Feldberg [Kaulsdorf]\",\"key\":\"379\"}],[\"so\",{\"caption\":\"Am Fenn [Steglitz]\",\"key\":\"380\"}],[\"so\",{\"caption\":\"Am Festplatz [Wedding]\",\"key\":\"381\"}],[\"so\",{\"caption\":\"Am Festungsgraben [Mitte]\",\"key\":\"382\"}],[\"so\",{\"caption\":\"Am Feuchten Winkel [Pankow]\",\"key\":\"383\"}],[\"so\",{\"caption\":\"Am Fichtenberg [Lichterfelde, Steglitz]\",\"key\":\"384\"}],[\"so\",{\"caption\":\"Am Filmlager [Köpenick]\",\"key\":\"385\"}],[\"so\",{\"caption\":\"Am Finkenherd [Falkenhagener Feld]\",\"key\":\"386\"}],[\"so\",{\"caption\":\"Am Fischtal [Zehlendorf]\",\"key\":\"387\"}],[\"so\",{\"caption\":\"Am Fliederbusch [Westend]\",\"key\":\"388\"}],[\"so\",{\"caption\":\"Am Fließ [Blankenburg]\",\"key\":\"389\"}],[\"so\",{\"caption\":\"Am Flugplatz Gatow [Kladow]\",\"key\":\"390\"}],[\"so\",{\"caption\":\"Am Flutgraben [Kreuzberg, Alt-Treptow]\",\"key\":\"391\"}],[\"so\",{\"caption\":\"Am Fölzberg [Lübars]\",\"key\":\"392\"}],[\"so\",{\"caption\":\"Am Forstacker [Hakenfelde]\",\"key\":\"393\"}],[\"so\",{\"caption\":\"Am Fort [Staaken]\",\"key\":\"394\"}],[\"so\",{\"caption\":\"Amfortasweg [Steglitz]\",\"key\":\"395\"}],[\"so\",{\"caption\":\"Am Freibad [Hermsdorf, Lübars]\",\"key\":\"396\"}],[\"so\",{\"caption\":\"Am Friedrichshain [Prenzlauer Berg]\",\"key\":\"397\"}],[\"so\",{\"caption\":\"Am Fuchsbau [Heiligensee]\",\"key\":\"398\"}],[\"so\",{\"caption\":\"Am Fuchspaß [Zehlendorf]\",\"key\":\"399\"}],[\"so\",{\"caption\":\"Am Gartenstadtweg [Bohnsdorf]\",\"key\":\"400\"}],[\"so\",{\"caption\":\"Am Gehrensee [Falkenberg]\",\"key\":\"401\"}],[\"so\",{\"caption\":\"Am Gemeindepark [Lankwitz]\",\"key\":\"402\"}],[\"so\",{\"caption\":\"Am Generalshof [Köpenick]\",\"key\":\"403\"}],[\"so\",{\"caption\":\"Am Genossenschaftsring [Wartenberg]\",\"key\":\"404\"}],[\"so\",{\"caption\":\"Am Gewerbepark [Biesdorf]\",\"key\":\"405\"}],[\"so\",{\"caption\":\"Am Glinigk [Altglienicke]\",\"key\":\"406\"}],[\"so\",{\"caption\":\"Am Glockenturm [Westend]\",\"key\":\"407\"}],[\"so\",{\"caption\":\"Am Goldmannpark [Friedrichshagen]\",\"key\":\"408\"}],[\"so\",{\"caption\":\"Am Graben [Karlshorst]\",\"key\":\"409\"}],[\"so\",{\"caption\":\"Am Graben [Stadtrandsiedlung Malchow]\",\"key\":\"410\"}],[\"so\",{\"caption\":\"Am Großen Rohrpfuhl [Rudow]\",\"key\":\"411\"}],[\"so\",{\"caption\":\"Am Großen Wannsee [Wannsee]\",\"key\":\"412\"}],[\"so\",{\"caption\":\"Am Grünen Anger [Johannisthal]\",\"key\":\"413\"}],[\"so\",{\"caption\":\"Am Grünen Hof [Frohnau]\",\"key\":\"414\"}],[\"so\",{\"caption\":\"Am Grünen Zipfel [Frohnau]\",\"key\":\"415\"}],[\"so\",{\"caption\":\"Am Grüngürtel [Wittenau]\",\"key\":\"416\"}],[\"so\",{\"caption\":\"Am Güterbahnhof Halensee [Halensee]\",\"key\":\"417\"}],[\"so\",{\"caption\":\"Am Gutshof [Wartenberg]\",\"key\":\"418\"}],[\"so\",{\"caption\":\"Am Gutspark [Lichtenberg]\",\"key\":\"419\"}],[\"so\",{\"caption\":\"Am Hain [Westend, Spandau]\",\"key\":\"420\"}],[\"so\",{\"caption\":\"Am Hamburger Bahnhof [Moabit]\",\"key\":\"421\"}],[\"so\",{\"caption\":\"Am Hanffgraben [Rudow]\",\"key\":\"422\"}],[\"so\",{\"caption\":\"Am Haselbusch [Johannisthal]\",\"key\":\"423\"}],[\"so\",{\"caption\":\"Am Havelgarten [Haselhorst]\",\"key\":\"424\"}],[\"so\",{\"caption\":\"Am Havelufer [Gatow]\",\"key\":\"425\"}],[\"so\",{\"caption\":\"Am Hechtgraben [Wartenberg]\",\"key\":\"426\"}],[\"so\",{\"caption\":\"Am Hegewinkel [Dahlem, Zehlendorf]\",\"key\":\"427\"}],[\"so\",{\"caption\":\"Am Heideberg [Staaken]\",\"key\":\"428\"}],[\"so\",{\"caption\":\"Am Heidebusch [Charlottenburg-Nord]\",\"key\":\"429\"}],[\"so\",{\"caption\":\"Am Heidefriedhof [Mariendorf]\",\"key\":\"430\"}],[\"so\",{\"caption\":\"Am Heidehof [Zehlendorf]\",\"key\":\"431\"}],[\"so\",{\"caption\":\"Am Heidesaum [Wannsee]\",\"key\":\"432\"}],[\"so\",{\"caption\":\"Am Heimenstein [Stadtrandsiedlung Malchow]\",\"key\":\"433\"}],[\"so\",{\"caption\":\"Am Heimhort [Falkenhagener Feld]\",\"key\":\"434\"}],[\"so\",{\"caption\":\"Am Hellespont [Mariendorf]\",\"key\":\"435\"}],[\"so\",{\"caption\":\"Am Hirschsprung [Dahlem]\",\"key\":\"436\"}],[\"so\",{\"caption\":\"Am Hirschwechsel [Heiligensee]\",\"key\":\"437\"}],[\"so\",{\"caption\":\"Am Hohen Feld [Karow]\",\"key\":\"438\"}],[\"so\",{\"caption\":\"Am Hohenzollernkanal [Tegel]\",\"key\":\"439\"}],[\"so\",{\"caption\":\"Am Horstenstein [Marienfelde]\",\"key\":\"440\"}],[\"so\",{\"caption\":\"Am Hügel [Wittenau]\",\"key\":\"441\"}],[\"so\",{\"caption\":\"Am Hüllepfuhl [Falkenhagener Feld]\",\"key\":\"442\"}],[\"so\",{\"caption\":\"Am Iderfenngraben [Niederschönhausen]\",\"key\":\"443\"}],[\"so\",{\"caption\":\"Am Igelgrund [Rosenthal]\",\"key\":\"444\"}],[\"so\",{\"caption\":\"Am Irissee [Britz]\",\"key\":\"445\"}],[\"so\",{\"caption\":\"Am Jartz [Lübars]\",\"key\":\"446\"}],[\"so\",{\"caption\":\"Am Johannistisch [Kreuzberg]\",\"key\":\"447\"}],[\"so\",{\"caption\":\"Am Juliusturm [Haselhorst, Spandau]\",\"key\":\"448\"}],[\"so\",{\"caption\":\"Am Kahlschlag [Frohnau]\",\"key\":\"449\"}],[\"so\",{\"caption\":\"Am Kanal [Grünau]\",\"key\":\"450\"}],[\"so\",{\"caption\":\"Am Kaniswall [Müggelheim]\",\"key\":\"451\"}],[\"so\",{\"caption\":\"Am Karlsbad [Tiergarten]\",\"key\":\"452\"}],[\"so\",{\"caption\":\"Am Karpfenpfuhl [Lichterfelde]\",\"key\":\"453\"}],[\"so\",{\"caption\":\"Am Kesselpfuhl [Wittenau]\",\"key\":\"454\"}],[\"so\",{\"caption\":\"Am Kiebitzpfuhl [Karow]\",\"key\":\"455\"}],[\"so\",{\"caption\":\"Am Kiefernhang [Gatow]\",\"key\":\"456\"}],[\"so\",{\"caption\":\"Am Kienpfuhl [Britz]\",\"key\":\"457\"}],[\"so\",{\"caption\":\"Am Kiesberg [Altglienicke]\",\"key\":\"458\"}],[\"so\",{\"caption\":\"Am Kiesteich [Falkenhagener Feld, Staaken]\",\"key\":\"459\"}],[\"so\",{\"caption\":\"Am Kietzer Feld [Köpenick]\",\"key\":\"460\"}],[\"so\",{\"caption\":\"Am Kinderdorf [Gatow]\",\"key\":\"461\"}],[\"so\",{\"caption\":\"Am Kirchendreieck [Kaulsdorf]\",\"key\":\"462\"}],[\"so\",{\"caption\":\"Am Kirchenland [Falkenhagener Feld]\",\"key\":\"463\"}],[\"so\",{\"caption\":\"Am Kirschgarten [Französisch Buchholz]\",\"key\":\"464\"}],[\"so\",{\"caption\":\"Am Kladower Wäldchen [Kladow]\",\"key\":\"465\"}],[\"so\",{\"caption\":\"Am Klarpfuhl [Rudow]\",\"key\":\"466\"}],[\"so\",{\"caption\":\"Am Klauswerder [Wittenau]\",\"key\":\"467\"}],[\"so\",{\"caption\":\"Am kleinen Anger [Wannsee]\",\"key\":\"468\"}],[\"so\",{\"caption\":\"Am Kleinen Platz [Staaken]\",\"key\":\"469\"}],[\"so\",{\"caption\":\"Am Kleinen Wannsee [Wannsee]\",\"key\":\"470\"}],[\"so\",{\"caption\":\"Am Kletterplatz [Wartenberg]\",\"key\":\"471\"}],[\"so\",{\"caption\":\"Am Klötzgraben [Lübars]\",\"key\":\"472\"}],[\"so\",{\"caption\":\"Am Koeltzepark [Spandau]\",\"key\":\"473\"}],[\"so\",{\"caption\":\"Am Köllnischen Park [Mitte]\",\"key\":\"474\"}],[\"so\",{\"caption\":\"Am Konsulat [Niederschönhausen]\",\"key\":\"475\"}],[\"so\",{\"caption\":\"Am Kornfeld [Kaulsdorf, Mahlsdorf]\",\"key\":\"476\"}],[\"so\",{\"caption\":\"Am Krähenberg [Konradshöhe]\",\"key\":\"477\"}],[\"so\",{\"caption\":\"Am Kringel [Frohnau]\",\"key\":\"478\"}],[\"so\",{\"caption\":\"Am Krögel [Mitte]\",\"key\":\"479\"}],[\"so\",{\"caption\":\"Am Krug [Staaken]\",\"key\":\"480\"}],[\"so\",{\"caption\":\"Am Krummen Weg [Staaken]\",\"key\":\"481\"}],[\"so\",{\"caption\":\"Am Krusenick [Köpenick]\",\"key\":\"482\"}],[\"so\",{\"caption\":\"Am Kupfergraben [Mitte]\",\"key\":\"483\"}],[\"so\",{\"caption\":\"Am Kurzen Weg [Staaken]\",\"key\":\"484\"}],[\"so\",{\"caption\":\"Am Küstergarten [Rahnsdorf]\",\"key\":\"485\"}],[\"so\",{\"caption\":\"Am Landeplatz [Wannsee]\",\"key\":\"486\"}],[\"so\",{\"caption\":\"Am Landschaftspark Gatow [Kladow]\",\"key\":\"487\"}],[\"so\",{\"caption\":\"Am Langen Weg [Staaken]\",\"key\":\"488\"}],[\"so\",{\"caption\":\"Am Lappjagen [Zehlendorf]\",\"key\":\"489\"}],[\"so\",{\"caption\":\"Am Laubwald [Siemensstadt]\",\"key\":\"490\"}],[\"so\",{\"caption\":\"Am Lehnshof [Hermsdorf]\",\"key\":\"491\"}],[\"so\",{\"caption\":\"Am Leitbruch [Waidmannslust]\",\"key\":\"492\"}],[\"so\",{\"caption\":\"Am Lindenplatz [Friedrichsfelde]\",\"key\":\"493\"}],[\"so\",{\"caption\":\"Am Lindenweg [Wartenberg]\",\"key\":\"494\"}],[\"so\",{\"caption\":\"Am Lokdepot [Schöneberg]\",\"key\":\"495\"}],[\"so\",{\"caption\":\"Am Löwentor [Wannsee]\",\"key\":\"496\"}],[\"so\",{\"caption\":\"Am Lübarser Feld [Lübars]\",\"key\":\"497\"}],[\"so\",{\"caption\":\"Am Luchgraben [Stadtrandsiedlung Malchow]\",\"key\":\"498\"}],[\"so\",{\"caption\":\"Am Lupinenfeld [Kaulsdorf, Mahlsdorf]\",\"key\":\"499\"}],[\"so\",{\"caption\":\"Am Lustgarten [Mitte]\",\"key\":\"500\"}],[\"so\",{\"caption\":\"Am Maria-Jankowski-Park [Köpenick]\",\"key\":\"501\"}],[\"so\",{\"caption\":\"Am Marienhain [Köpenick]\",\"key\":\"502\"}],[\"so\",{\"caption\":\"Am Maselakepark [Hakenfelde]\",\"key\":\"503\"}],[\"so\",{\"caption\":\"Am Meisenwinkel [Rosenthal]\",\"key\":\"504\"}],[\"so\",{\"caption\":\"Ammerseestraße [Grünau]\",\"key\":\"505\"}],[\"so\",{\"caption\":\"Am Mickelbruch [Britz]\",\"key\":\"506\"}],[\"so\",{\"caption\":\"Am Moosbruch [Kaulsdorf]\",\"key\":\"507\"}],[\"so\",{\"caption\":\"Am Müggelberg [Müggelheim]\",\"key\":\"508\"}],[\"so\",{\"caption\":\"Am Müggelpark [Müggelheim]\",\"key\":\"509\"}],[\"so\",{\"caption\":\"Am Müggelsee [Köpenick]\",\"key\":\"510\"}],[\"so\",{\"caption\":\"Am Mühlenberg [Schöneberg]\",\"key\":\"511\"}],[\"so\",{\"caption\":\"Am Mühlenfließ [Rahnsdorf]\",\"key\":\"512\"}],[\"so\",{\"caption\":\"Am Niederfeld [Kaulsdorf]\",\"key\":\"513\"}],[\"so\",{\"caption\":\"Am Nordbahnhof [Mitte]\",\"key\":\"514\"}],[\"so\",{\"caption\":\"Am Nordgraben [Borsigwalde, Reinickendorf, Wittenau]\",\"key\":\"515\"}],[\"so\",{\"caption\":\"Am Nordhafen [Wedding]\",\"key\":\"516\"}],[\"so\",{\"caption\":\"Am Nußbaum [Mitte]\",\"key\":\"517\"}],[\"so\",{\"caption\":\"Am Oberbaum [Friedrichshain]\",\"key\":\"518\"}],[\"so\",{\"caption\":\"Am Oberhafen [Neukölln]\",\"key\":\"519\"}],[\"so\",{\"caption\":\"Am Oberhafen [Spandau]\",\"key\":\"520\"}],[\"so\",{\"caption\":\"Am Oder-Spree-Kanal [Schmöckwitz]\",\"key\":\"521\"}],[\"so\",{\"caption\":\"Am Omnibushof [Wilhelmstadt]\",\"key\":\"522\"}],[\"so\",{\"caption\":\"Am Orangeriepark [Niederschönhausen]\",\"key\":\"523\"}],[\"so\",{\"caption\":\"Amorbacher Weg [Hakenfelde]\",\"key\":\"524\"}],[\"so\",{\"caption\":\"Amorstraße [Bohnsdorf]\",\"key\":\"525\"}],[\"so\",{\"caption\":\"Am Ortsrand [Gatow]\",\"key\":\"526\"}],[\"so\",{\"caption\":\"Am Osrücken [Lübars]\",\"key\":\"527\"}],[\"so\",{\"caption\":\"Am Ostbahnhof [Friedrichshain]\",\"key\":\"528\"}],[\"so\",{\"caption\":\"Am Pankepark [Mitte]\",\"key\":\"529\"}],[\"so\",{\"caption\":\"Am Park [Tiergarten]\",\"key\":\"530\"}],[\"so\",{\"caption\":\"Am Petersberg [Dahlem]\",\"key\":\"531\"}],[\"so\",{\"caption\":\"Am Pfarracker [Lichterfelde]\",\"key\":\"532\"}],[\"so\",{\"caption\":\"Ampferweg [Rudow]\",\"key\":\"533\"}],[\"so\",{\"caption\":\"Am Pfingstberg [Frohnau, Hermsdorf]\",\"key\":\"534\"}],[\"so\",{\"caption\":\"Am Pfuhl [Lichterfelde]\",\"key\":\"535\"}],[\"so\",{\"caption\":\"Am Pichelssee [Wilhelmstadt]\",\"key\":\"536\"}],[\"so\",{\"caption\":\"Am Pilz [Frohnau]\",\"key\":\"537\"}],[\"so\",{\"caption\":\"Am Plänterwald [Plänterwald]\",\"key\":\"538\"}],[\"so\",{\"caption\":\"Am Plumpengraben [Bohnsdorf]\",\"key\":\"539\"}],[\"so\",{\"caption\":\"Am Poloplatz [Frohnau]\",\"key\":\"540\"}],[\"so\",{\"caption\":\"Am Posseberg [Buch]\",\"key\":\"541\"}],[\"so\",{\"caption\":\"Am Postbahnhof [Friedrichshain]\",\"key\":\"542\"}],[\"so\",{\"caption\":\"Am Postfenn [Grunewald, Westend]\",\"key\":\"543\"}],[\"so\",{\"caption\":\"Am Priesteracker [Wittenau]\",\"key\":\"544\"}],[\"so\",{\"caption\":\"Am Priesterberg [Frohnau]\",\"key\":\"545\"}],[\"so\",{\"caption\":\"Am Pumpwerk [Altglienicke]\",\"key\":\"546\"}],[\"so\",{\"caption\":\"Am Querschlag [Frohnau]\",\"key\":\"547\"}],[\"so\",{\"caption\":\"Am Rain [Staaken]\",\"key\":\"548\"}],[\"so\",{\"caption\":\"Am Rathaus [Schöneberg]\",\"key\":\"549\"}],[\"so\",{\"caption\":\"Am Rathauspark [Wittenau]\",\"key\":\"550\"}],[\"so\",{\"caption\":\"Am Rehwechsel [Zehlendorf]\",\"key\":\"551\"}],[\"so\",{\"caption\":\"Am Rheinischen Viertel [Karlshorst]\",\"key\":\"552\"}],[\"so\",{\"caption\":\"Am Ried [Hermsdorf]\",\"key\":\"553\"}],[\"so\",{\"caption\":\"Am Ritterholz [Kladow]\",\"key\":\"554\"}],[\"so\",{\"caption\":\"Am Rodelberg [Lübars]\",\"key\":\"555\"}],[\"so\",{\"caption\":\"Am Rohrbusch [Lübars]\",\"key\":\"556\"}],[\"so\",{\"caption\":\"Am Rohrgarten [Nikolassee]\",\"key\":\"557\"}],[\"so\",{\"caption\":\"Am Rollberg [Rosenthal]\",\"key\":\"558\"}],[\"so\",{\"caption\":\"Am Römersgrün [Britz]\",\"key\":\"559\"}],[\"so\",{\"caption\":\"Am Rosenanger [Frohnau]\",\"key\":\"560\"}],[\"so\",{\"caption\":\"Am Rosenhag [Kaulsdorf, Mahlsdorf]\",\"key\":\"561\"}],[\"so\",{\"caption\":\"Am Rosensteg [Tegel]\",\"key\":\"562\"}],[\"so\",{\"caption\":\"Am Roten Stein [Kladow]\",\"key\":\"563\"}],[\"so\",{\"caption\":\"Am Rötepfuhl [Buckow]\",\"key\":\"564\"}],[\"so\",{\"caption\":\"Am Ruderverein [Grünau]\",\"key\":\"565\"}],[\"so\",{\"caption\":\"Am Rudolfplatz [Friedrichshain]\",\"key\":\"566\"}],[\"so\",{\"caption\":\"Am Rudower Waldrand [Rudow]\",\"key\":\"567\"}],[\"so\",{\"caption\":\"Amrumer Straße [Wedding]\",\"key\":\"568\"}],[\"so\",{\"caption\":\"Am Rundling [Johannisthal]\",\"key\":\"569\"}],[\"so\",{\"caption\":\"Am Rupenhorn [Westend]\",\"key\":\"570\"}],[\"so\",{\"caption\":\"Am Sandberg [Karlshorst]\",\"key\":\"571\"}],[\"so\",{\"caption\":\"Am Sandhaus [Buch]\",\"key\":\"572\"}],[\"so\",{\"caption\":\"Am Sandwerder [Wannsee]\",\"key\":\"573\"}],[\"so\",{\"caption\":\"Am Schäfersee [Reinickendorf]\",\"key\":\"574\"}],[\"so\",{\"caption\":\"Am Schilf [Wartenberg]\",\"key\":\"575\"}],[\"so\",{\"caption\":\"Am Schillertheater [Charlottenburg]\",\"key\":\"576\"}],[\"so\",{\"caption\":\"Am Schlachtensee [Nikolassee, Zehlendorf]\",\"key\":\"577\"}],[\"so\",{\"caption\":\"Am Schlangengraben [Spandau]\",\"key\":\"578\"}],[\"so\",{\"caption\":\"Am Schlehdorn [Mahlsdorf]\",\"key\":\"579\"}],[\"so\",{\"caption\":\"Am Schloßberg [Köpenick]\",\"key\":\"580\"}],[\"so\",{\"caption\":\"Am Schloßhof [Biesdorf]\",\"key\":\"581\"}],[\"so\",{\"caption\":\"Am Schloßpark [Niederschönhausen, Pankow]\",\"key\":\"582\"}],[\"so\",{\"caption\":\"Am Schmeding [Marzahn]\",\"key\":\"583\"}],[\"so\",{\"caption\":\"Am Schonungsberg [Rahnsdorf]\",\"key\":\"584\"}],[\"so\",{\"caption\":\"Am Schülerheim [Dahlem]\",\"key\":\"585\"}],[\"so\",{\"caption\":\"Am Schweizer Garten [Prenzlauer Berg]\",\"key\":\"586\"}],[\"so\",{\"caption\":\"Am Schweizerhof [Zehlendorf]\",\"key\":\"587\"}],[\"so\",{\"caption\":\"Am Schwemmhorn [Kladow]\",\"key\":\"588\"}],[\"so\",{\"caption\":\"Am Seddinsee [Schmöckwitz]\",\"key\":\"589\"}],[\"so\",{\"caption\":\"Am Seeschloß [Hermsdorf]\",\"key\":\"590\"}],[\"so\",{\"caption\":\"Amselgrund [Hermsdorf]\",\"key\":\"591\"}],[\"so\",{\"caption\":\"Amselhainer Weg [Marzahn]\",\"key\":\"592\"}],[\"so\",{\"caption\":\"Amselstraße [Karow]\",\"key\":\"593\"}],[\"so\",{\"caption\":\"Amselstraße [Schmargendorf, Dahlem]\",\"key\":\"594\"}],[\"so\",{\"caption\":\"Amselweg [Bohnsdorf]\",\"key\":\"595\"}],[\"so\",{\"caption\":\"Am Sonnenhügel [Staaken]\",\"key\":\"596\"}],[\"so\",{\"caption\":\"Am Spandauer Wasserturm [Spandau]\",\"key\":\"597\"}],[\"so\",{\"caption\":\"Am Speicher [Friedrichshain]\",\"key\":\"598\"}],[\"so\",{\"caption\":\"Am Spielplatz [Köpenick]\",\"key\":\"599\"}],[\"so\",{\"caption\":\"Am Spreebord [Charlottenburg]\",\"key\":\"600\"}],[\"so\",{\"caption\":\"Am Springebruch [Lübars]\",\"key\":\"601\"}],[\"so\",{\"caption\":\"Am Staakener Kirchengelände [Staaken]\",\"key\":\"602\"}],[\"so\",{\"caption\":\"Am Stadtforst [Köpenick]\",\"key\":\"603\"}],[\"so\",{\"caption\":\"Am Stadtpark [Lichtenberg]\",\"key\":\"604\"}],[\"so\",{\"caption\":\"Am Stadtpark [Steglitz]\",\"key\":\"605\"}],[\"so\",{\"caption\":\"Am Stand [Reinickendorf]\",\"key\":\"606\"}],[\"so\",{\"caption\":\"Am Steilhang [Müggelheim]\",\"key\":\"607\"}],[\"so\",{\"caption\":\"Am Steinberg [Heinersdorf, Weißensee]\",\"key\":\"608\"}],[\"so\",{\"caption\":\"Am Steinbergpark [Wittenau]\",\"key\":\"609\"}],[\"so\",{\"caption\":\"Am Stener Berg [Buch]\",\"key\":\"610\"}],[\"so\",{\"caption\":\"Amsterdamer Straße [Wedding]\",\"key\":\"611\"}],[\"so\",{\"caption\":\"Am Stichkanal [Lichterfelde]\",\"key\":\"612\"}],[\"so\",{\"caption\":\"Am Stieggarten [Rahnsdorf]\",\"key\":\"613\"}],[\"so\",{\"caption\":\"Am Straßenbahnhof [Britz]\",\"key\":\"614\"}],[\"so\",{\"caption\":\"Am Studio [Adlershof]\",\"key\":\"615\"}],[\"so\",{\"caption\":\"Am Südfeld [Heiligensee]\",\"key\":\"616\"}],[\"so\",{\"caption\":\"Am Sudhaus [Neukölln]\",\"key\":\"617\"}],[\"so\",{\"caption\":\"Am Südpark [Wilhelmstadt]\",\"key\":\"618\"}],[\"so\",{\"caption\":\"Am Tegeler Hafen [Tegel]\",\"key\":\"619\"}],[\"so\",{\"caption\":\"Am Tegelgrund [Heiligensee]\",\"key\":\"620\"}],[\"so\",{\"caption\":\"Am Tempelgraben [Rosenthal]\",\"key\":\"621\"}],[\"so\",{\"caption\":\"Am Tempelhofer Berg [Kreuzberg]\",\"key\":\"622\"}],[\"so\",{\"caption\":\"Am Teufelsbruch [Hakenfelde]\",\"key\":\"623\"}],[\"so\",{\"caption\":\"Am Theodorpark [Mahlsdorf]\",\"key\":\"624\"}],[\"so\",{\"caption\":\"Am Tierpark [Friedrichsfelde]\",\"key\":\"625\"}],[\"so\",{\"caption\":\"Am Treptower Park [Alt-Treptow, Plänterwald]\",\"key\":\"626\"}],[\"so\",{\"caption\":\"Am-Treptower-Park-Brücke [Plänterwald]\",\"key\":\"627\"}],[\"so\",{\"caption\":\"Am Triftpark [Wittenau]\",\"key\":\"628\"}],[\"so\",{\"caption\":\"Amtsgerichtsplatz [Charlottenburg]\",\"key\":\"629\"}],[\"so\",{\"caption\":\"Amtsstraße [Köpenick]\",\"key\":\"630\"}],[\"so\",{\"caption\":\"Am Unterholz [Heiligensee]\",\"key\":\"631\"}],[\"so\",{\"caption\":\"Am Viehhof [Prenzlauer Berg]\",\"key\":\"632\"}],[\"so\",{\"caption\":\"Am Vierling [Zehlendorf]\",\"key\":\"633\"}],[\"so\",{\"caption\":\"Am Vierrutenberg [Lübars]\",\"key\":\"634\"}],[\"so\",{\"caption\":\"Am Vierstückenpfuhl [Zehlendorf]\",\"key\":\"635\"}],[\"so\",{\"caption\":\"Am Vogelherd [Westend]\",\"key\":\"636\"}],[\"so\",{\"caption\":\"Am Volkspark [Wilmersdorf]\",\"key\":\"637\"}],[\"so\",{\"caption\":\"Am Vorwerk [Buch]\",\"key\":\"638\"}],[\"so\",{\"caption\":\"Am Waidmannseck [Wittenau]\",\"key\":\"639\"}],[\"so\",{\"caption\":\"Am Waldberg [Biesdorf]\",\"key\":\"640\"}],[\"so\",{\"caption\":\"Am Wäldchen [Staaken]\",\"key\":\"641\"}],[\"so\",{\"caption\":\"Am Walde [Karlshorst]\",\"key\":\"642\"}],[\"so\",{\"caption\":\"Am Waldfriedhof [Dahlem]\",\"key\":\"643\"}],[\"so\",{\"caption\":\"Am Waldhaus [Nikolassee]\",\"key\":\"644\"}],[\"so\",{\"caption\":\"Am Waldidyll [Hermsdorf]\",\"key\":\"645\"}],[\"so\",{\"caption\":\"Am Waldpark [Hermsdorf]\",\"key\":\"646\"}],[\"so\",{\"caption\":\"Am Waldrand [Wannsee]\",\"key\":\"647\"}],[\"so\",{\"caption\":\"Am Wall [Spandau]\",\"key\":\"648\"}],[\"so\",{\"caption\":\"Am Wartenberger Luch [Wartenberg]\",\"key\":\"649\"}],[\"so\",{\"caption\":\"Am Wasserbogen [Hakenfelde]\",\"key\":\"650\"}],[\"so\",{\"caption\":\"Am Wasserturm [Heinersdorf]\",\"key\":\"651\"}],[\"so\",{\"caption\":\"Am Wasserwerk [Lichtenberg]\",\"key\":\"652\"}],[\"so\",{\"caption\":\"Am Wechsel [Waidmannslust]\",\"key\":\"653\"}],[\"so\",{\"caption\":\"Am Weidenbruch [Biesdorf]\",\"key\":\"654\"}],[\"so\",{\"caption\":\"Am Weidendamm [Mitte]\",\"key\":\"655\"}],[\"so\",{\"caption\":\"Am Weihenhorst [Karlshorst]\",\"key\":\"656\"}],[\"so\",{\"caption\":\"Am Weingarten [Prenzlauer Berg]\",\"key\":\"657\"}],[\"so\",{\"caption\":\"Am Weinhang [Kreuzberg]\",\"key\":\"658\"}],[\"so\",{\"caption\":\"Am Weißen Steg [Zehlendorf]\",\"key\":\"659\"}],[\"so\",{\"caption\":\"Am Werksgarten [Köpenick]\",\"key\":\"660\"}],[\"so\",{\"caption\":\"Am Westkreuz [Charlottenburg, Westend]\",\"key\":\"661\"}],[\"so\",{\"caption\":\"Am Wieselbau [Zehlendorf]\",\"key\":\"662\"}],[\"so\",{\"caption\":\"Am Wiesenende [Lübars]\",\"key\":\"663\"}],[\"so\",{\"caption\":\"Am Wiesengraben [Köpenick]\",\"key\":\"664\"}],[\"so\",{\"caption\":\"Am Wiesengrund [Rosenthal]\",\"key\":\"665\"}],[\"so\",{\"caption\":\"Am Wiesenhang [Kaulsdorf]\",\"key\":\"666\"}],[\"so\",{\"caption\":\"Am Wiesenhaus [Gatow]\",\"key\":\"667\"}],[\"so\",{\"caption\":\"Am Wiesenrain [Friedrichshagen]\",\"key\":\"668\"}],[\"so\",{\"caption\":\"Am Wiesenweg [Bohnsdorf]\",\"key\":\"669\"}],[\"so\",{\"caption\":\"Am Wildbusch [Müggelheim]\",\"key\":\"670\"}],[\"so\",{\"caption\":\"Am Wildgatter [Wannsee]\",\"key\":\"671\"}],[\"so\",{\"caption\":\"Am Winkel [Altglienicke]\",\"key\":\"672\"}],[\"so\",{\"caption\":\"Am Wriezener Bahnhof [Friedrichshain]\",\"key\":\"673\"}],[\"so\",{\"caption\":\"Am Wuhlebogen [Kaulsdorf]\",\"key\":\"674\"}],[\"so\",{\"caption\":\"Am Zeppelinpark [Staaken]\",\"key\":\"675\"}],[\"so\",{\"caption\":\"Am Zeughaus [Mitte]\",\"key\":\"676\"}],[\"so\",{\"caption\":\"Am Zirkus [Mitte]\",\"key\":\"677\"}],[\"so\",{\"caption\":\"Am Zwiebusch [Schmöckwitz]\",\"key\":\"678\"}],[\"so\",{\"caption\":\"Am Zwirngraben [Mitte]\",\"key\":\"679\"}],[\"so\",{\"caption\":\"Ancillonweg [Französisch Buchholz]\",\"key\":\"680\"}],[\"so\",{\"caption\":\"Andanteweg [Rosenthal]\",\"key\":\"681\"}],[\"so\",{\"caption\":\"An den Achterhöfen [Buckow]\",\"key\":\"682\"}],[\"so\",{\"caption\":\"An den Auen [Falkenberg]\",\"key\":\"683\"}],[\"so\",{\"caption\":\"An den Bänken [Rahnsdorf]\",\"key\":\"684\"}],[\"so\",{\"caption\":\"An den Berggärten [Gatow]\",\"key\":\"685\"}],[\"so\",{\"caption\":\"An den Eldenaer Höfen [Friedrichshain, Prenzlauer Berg]\",\"key\":\"686\"}],[\"so\",{\"caption\":\"An den Feldern [Buckow]\",\"key\":\"687\"}],[\"so\",{\"caption\":\"An den Feldtmanngärten [Weißensee]\",\"key\":\"688\"}],[\"so\",{\"caption\":\"An den Fließtalhöfen [Hermsdorf]\",\"key\":\"689\"}],[\"so\",{\"caption\":\"An den Freiheitswiesen [Spandau]\",\"key\":\"690\"}],[\"so\",{\"caption\":\"An den Haselbüschen [Haselhorst]\",\"key\":\"691\"}],[\"so\",{\"caption\":\"An den Hubertshäusern [Nikolassee]\",\"key\":\"692\"}],[\"so\",{\"caption\":\"An den Klostergärten [Marienfelde]\",\"key\":\"693\"}],[\"so\",{\"caption\":\"An den Knabenhäusern [Rummelsburg]\",\"key\":\"694\"}],[\"so\",{\"caption\":\"An den Rohrbruchwiesen [Haselhorst]\",\"key\":\"695\"}],[\"so\",{\"caption\":\"An den Siedlergärten [Mahlsdorf]\",\"key\":\"696\"}],[\"so\",{\"caption\":\"An den Treptowers [Alt-Treptow]\",\"key\":\"697\"}],[\"so\",{\"caption\":\"Andenzeisigweg [Blankenburg]\",\"key\":\"698\"}],[\"so\",{\"caption\":\"An den Zingerwiesen [Niederschönhausen]\",\"key\":\"699\"}],[\"so\",{\"caption\":\"An der Apostelkirche [Schöneberg]\",\"key\":\"700\"}],[\"so\",{\"caption\":\"An der Aussicht [Heiligensee]\",\"key\":\"701\"}],[\"so\",{\"caption\":\"An der Bastion [Kladow]\",\"key\":\"702\"}],[\"so\",{\"caption\":\"An der Brauerei [Friedrichshain]\",\"key\":\"703\"}],[\"so\",{\"caption\":\"An der Brücke [Grünau]\",\"key\":\"704\"}],[\"so\",{\"caption\":\"An der Buche [Frohnau]\",\"key\":\"705\"}],[\"so\",{\"caption\":\"An der Bucht [Rummelsburg]\",\"key\":\"706\"}],[\"so\",{\"caption\":\"An der Dahme [Grünau]\",\"key\":\"707\"}],[\"so\",{\"caption\":\"An der Dorfkirche [Marienfelde]\",\"key\":\"708\"}],[\"so\",{\"caption\":\"An der Felgenlake [Falkenhagener Feld]\",\"key\":\"709\"}],[\"so\",{\"caption\":\"An der Filmfabrik [Köpenick]\",\"key\":\"710\"}],[\"so\",{\"caption\":\"An der Fließwiese [Westend]\",\"key\":\"711\"}],[\"so\",{\"caption\":\"An der Gatower Heide [Kladow]\",\"key\":\"712\"}],[\"so\",{\"caption\":\"An der Hasenfurt [Heiligensee]\",\"key\":\"713\"}],[\"so\",{\"caption\":\"An der Havelspitze [Hakenfelde]\",\"key\":\"714\"}],[\"so\",{\"caption\":\"An der Heide [Tegel]\",\"key\":\"715\"}],[\"so\",{\"caption\":\"An der Heilandsweide [Marienfelde]\",\"key\":\"716\"}],[\"so\",{\"caption\":\"An der Himmelswiese [Müggelheim]\",\"key\":\"717\"}],[\"so\",{\"caption\":\"An der Industriebahn [Weißensee]\",\"key\":\"718\"}],[\"so\",{\"caption\":\"An der Kappe [Falkenhagener Feld, Spandau]\",\"key\":\"719\"}],[\"so\",{\"caption\":\"An der Karlshorster Heide [Karlshorst]\",\"key\":\"720\"}],[\"so\",{\"caption\":\"An der Karolinenhöhe [Staaken, Wilhelmstadt]\",\"key\":\"721\"}],[\"so\",{\"caption\":\"An der Kieler Brücke [Mitte, Wedding]\",\"key\":\"722\"}],[\"so\",{\"caption\":\"An der Kolonnade [Mitte]\",\"key\":\"723\"}],[\"so\",{\"caption\":\"An der Kommandantur [Mitte]\",\"key\":\"724\"}],[\"so\",{\"caption\":\"An der Koppel [Reinickendorf]\",\"key\":\"725\"}],[\"so\",{\"caption\":\"An der Krähenheide [Konradshöhe]\",\"key\":\"726\"}],[\"so\",{\"caption\":\"An der Kremmener Bahn [Heiligensee]\",\"key\":\"727\"}],[\"so\",{\"caption\":\"An der Krummen Lake [Müggelheim]\",\"key\":\"728\"}],[\"so\",{\"caption\":\"An der Laake [Karow]\",\"key\":\"729\"}],[\"so\",{\"caption\":\"An der Mäckeritzbrücke [Tegel]\",\"key\":\"730\"}],[\"so\",{\"caption\":\"An der Malche [Tegel]\",\"key\":\"731\"}],[\"so\",{\"caption\":\"An der Margaretenhöhe [Wartenberg]\",\"key\":\"732\"}],[\"so\",{\"caption\":\"An der Michaelbrücke [Friedrichshain]\",\"key\":\"733\"}],[\"so\",{\"caption\":\"An der Mühle [Tegel]\",\"key\":\"734\"}],[\"so\",{\"caption\":\"Andernacher Straße [Karlshorst]\",\"key\":\"735\"}],[\"so\",{\"caption\":\"An der Nachtbucht [Rudow]\",\"key\":\"736\"}],[\"so\",{\"caption\":\"An der Neumark [Britz]\",\"key\":\"737\"}],[\"so\",{\"caption\":\"An der Oberrealschule [Tegel]\",\"key\":\"738\"}],[\"so\",{\"caption\":\"An der Obstwiese [Wannsee]\",\"key\":\"739\"}],[\"so\",{\"caption\":\"An der Ostbahn [Friedrichshain]\",\"key\":\"740\"}],[\"so\",{\"caption\":\"An der Priesterkoppel [Rosenthal]\",\"key\":\"741\"}],[\"so\",{\"caption\":\"An der Putlitzbrücke [Moabit]\",\"key\":\"742\"}],[\"so\",{\"caption\":\"An der Rehwiese [Nikolassee]\",\"key\":\"743\"}],[\"so\",{\"caption\":\"An der Rudower Höhe [Rudow]\",\"key\":\"744\"}],[\"so\",{\"caption\":\"An der Schäferei [Lichterfelde]\",\"key\":\"745\"}],[\"so\",{\"caption\":\"An der Schillingbrücke [Friedrichshain]\",\"key\":\"746\"}],[\"so\",{\"caption\":\"An der Schneise [Heiligensee]\",\"key\":\"747\"}],[\"so\",{\"caption\":\"An der Schule [Mahlsdorf]\",\"key\":\"748\"}],[\"so\",{\"caption\":\"Andersenstraße [Prenzlauer Berg]\",\"key\":\"749\"}],[\"so\",{\"caption\":\"An der Spandauer Brücke [Mitte]\",\"key\":\"750\"}],[\"so\",{\"caption\":\"An der Spitze [Staaken]\",\"key\":\"751\"}],[\"so\",{\"caption\":\"An der Spreeschanze [Haselhorst]\",\"key\":\"752\"}],[\"so\",{\"caption\":\"An der Tränke [Falkenhagener Feld]\",\"key\":\"753\"}],[\"so\",{\"caption\":\"An der Urania [Schöneberg]\",\"key\":\"754\"}],[\"so\",{\"caption\":\"An der Vogelweide [Rosenthal]\",\"key\":\"755\"}],[\"so\",{\"caption\":\"An der Wasserstadt [Hakenfelde]\",\"key\":\"756\"}],[\"so\",{\"caption\":\"An der Werderlake [Rudow]\",\"key\":\"757\"}],[\"so\",{\"caption\":\"An der Wildbahn [Heiligensee]\",\"key\":\"758\"}],[\"so\",{\"caption\":\"An der Wuhle [Biesdorf, Kaulsdorf]\",\"key\":\"759\"}],[\"so\",{\"caption\":\"An der Wuhlheide [Köpenick, Oberschöneweide]\",\"key\":\"760\"}],[\"so\",{\"caption\":\"Andlauer Weg [Mariendorf]\",\"key\":\"761\"}],[\"so\",{\"caption\":\"Andornsteig [Heiligensee]\",\"key\":\"762\"}],[\"so\",{\"caption\":\"Andreasberger Straße [Britz]\",\"key\":\"763\"}],[\"so\",{\"caption\":\"Andreas-Hofer-Platz [Pankow]\",\"key\":\"764\"}],[\"so\",{\"caption\":\"Andreasstraße [Friedrichshain]\",\"key\":\"765\"}],[\"so\",{\"caption\":\"Andréezeile [Zehlendorf]\",\"key\":\"766\"}],[\"so\",{\"caption\":\"Anemonensteig [Karlshorst]\",\"key\":\"767\"}],[\"so\",{\"caption\":\"Anemonenstraße [Köpenick]\",\"key\":\"768\"}],[\"so\",{\"caption\":\"Angelikaweg [Buckow, Rudow]\",\"key\":\"769\"}],[\"so\",{\"caption\":\"Angerburger Allee [Westend]\",\"key\":\"770\"}],[\"so\",{\"caption\":\"Angermünder Straße [Lichtenrade]\",\"key\":\"771\"}],[\"so\",{\"caption\":\"Angermünder Straße [Prenzlauer Berg]\",\"key\":\"772\"}],[\"so\",{\"caption\":\"Angersbacher Pfad [Wittenau]\",\"key\":\"773\"}],[\"so\",{\"caption\":\"Angersteinweg [Köpenick]\",\"key\":\"774\"}],[\"so\",{\"caption\":\"Angerweg [Rosenthal]\",\"key\":\"775\"}],[\"so\",{\"caption\":\"Anglersiedlung [Heiligensee]\",\"key\":\"776\"}],[\"so\",{\"caption\":\"Anhalter Straße [Kreuzberg]\",\"key\":\"777\"}],[\"so\",{\"caption\":\"Anhaltinerstraße [Zehlendorf]\",\"key\":\"778\"}],[\"so\",{\"caption\":\"Anisweg [Rosenthal]\",\"key\":\"779\"}],[\"so\",{\"caption\":\"Ankerweg [Grünau]\",\"key\":\"780\"}],[\"so\",{\"caption\":\"Anklamer Straße [Mitte]\",\"key\":\"781\"}],[\"so\",{\"caption\":\"Ankogelweg [Buckow, Mariendorf]\",\"key\":\"782\"}],[\"so\",{\"caption\":\"Anna-Bruseberg-Straße [Französisch Buchholz]\",\"key\":\"783\"}],[\"so\",{\"caption\":\"Annaburger Straße [Hellersdorf]\",\"key\":\"784\"}],[\"so\",{\"caption\":\"Anna-Ebermann-Straße [Alt-Hohenschönhausen]\",\"key\":\"785\"}],[\"so\",{\"caption\":\"Anna-Louisa-Karsch-Straße [Mitte]\",\"key\":\"786\"}],[\"so\",{\"caption\":\"Anna-Mackenroth-Weg [Lichterfelde]\",\"key\":\"787\"}],[\"so\",{\"caption\":\"Anna-Maria-Müller-Straße [Alt-Hohenschönhausen]\",\"key\":\"788\"}],[\"so\",{\"caption\":\"Anna-Nemitz-Brücke [Baumschulenweg]\",\"key\":\"789\"}],[\"so\",{\"caption\":\"Anna-Nemitz-Weg [Gropiusstadt]\",\"key\":\"790\"}],[\"so\",{\"caption\":\"Anna-Seghers-Straße [Adlershof]\",\"key\":\"791\"}],[\"so\",{\"caption\":\"Anna-Siemsen-Weg [Gropiusstadt]\",\"key\":\"792\"}],[\"so\",{\"caption\":\"Annastraße [Lankwitz]\",\"key\":\"793\"}],[\"so\",{\"caption\":\"Anne-Frank-Straße [Altglienicke]\",\"key\":\"794\"}],[\"so\",{\"caption\":\"Annemariestraße [Alt-Hohenschönhausen]\",\"key\":\"795\"}],[\"so\",{\"caption\":\"Annemirl-Bauer-Platz [Friedrichshain]\",\"key\":\"796\"}],[\"so\",{\"caption\":\"Annenallee [Köpenick]\",\"key\":\"797\"}],[\"so\",{\"caption\":\"Annenstraße [Biesdorf]\",\"key\":\"798\"}],[\"so\",{\"caption\":\"Annenstraße [Mitte]\",\"key\":\"799\"}],[\"so\",{\"caption\":\"Annweilerweg [Müggelheim]\",\"key\":\"800\"}],[\"so\",{\"caption\":\"Ansbacher Straße [Schöneberg]\",\"key\":\"801\"}],[\"so\",{\"caption\":\"Anschützweg [Staaken]\",\"key\":\"802\"}],[\"so\",{\"caption\":\"Anselmstraße [Biesdorf]\",\"key\":\"803\"}],[\"so\",{\"caption\":\"Ansgarstraße [Frohnau]\",\"key\":\"804\"}],[\"so\",{\"caption\":\"Antonienstraße [Reinickendorf]\",\"key\":\"805\"}],[\"so\",{\"caption\":\"Antoniuskirchstraße [Oberschöneweide]\",\"key\":\"806\"}],[\"so\",{\"caption\":\"Antonplatz [Weißensee]\",\"key\":\"807\"}],[\"so\",{\"caption\":\"Anton-Saefkow-Platz [Fennpfuhl]\",\"key\":\"808\"}],[\"so\",{\"caption\":\"Anton-Saefkow-Straße [Prenzlauer Berg]\",\"key\":\"809\"}],[\"so\",{\"caption\":\"Antonstraße [Wedding]\",\"key\":\"810\"}],[\"so\",{\"caption\":\"Anton-von-Werner-Straße [Kaulsdorf]\",\"key\":\"811\"}],[\"so\",{\"caption\":\"Anton-Webern-Weg [Rosenthal]\",\"key\":\"812\"}],[\"so\",{\"caption\":\"Antwerpener Straße [Wedding]\",\"key\":\"813\"}],[\"so\",{\"caption\":\"Anzengruberstraße [Neukölln]\",\"key\":\"814\"}],[\"so\",{\"caption\":\"Apfelblütenweg [Französisch Buchholz]\",\"key\":\"815\"}],[\"so\",{\"caption\":\"Apfelweg [Altglienicke]\",\"key\":\"816\"}],[\"so\",{\"caption\":\"Apfelwicklerstraße [Biesdorf]\",\"key\":\"817\"}],[\"so\",{\"caption\":\"Apoldaer Straße [Lankwitz]\",\"key\":\"818\"}],[\"so\",{\"caption\":\"Apollofalterallee [Biesdorf]\",\"key\":\"819\"}],[\"so\",{\"caption\":\"Apollostraße [Bohnsdorf]\",\"key\":\"820\"}],[\"so\",{\"caption\":\"Apostel-Paulus-Straße [Schöneberg]\",\"key\":\"821\"}],[\"so\",{\"caption\":\"Appelbacher Weg [Müggelheim]\",\"key\":\"822\"}],[\"so\",{\"caption\":\"Appenzeller Straße [Lichterfelde]\",\"key\":\"823\"}],[\"so\",{\"caption\":\"Aprikosensteig [Baumschulenweg]\",\"key\":\"824\"}],[\"so\",{\"caption\":\"Arabisweg [Rudow]\",\"key\":\"825\"}],[\"so\",{\"caption\":\"Aralienweg [Rosenthal]\",\"key\":\"826\"}],[\"so\",{\"caption\":\"Arberstraße [Karlshorst]\",\"key\":\"827\"}],[\"so\",{\"caption\":\"Archenholdstraße [Friedrichsfelde]\",\"key\":\"828\"}],[\"so\",{\"caption\":\"Archibaldweg [Rummelsburg]\",\"key\":\"829\"}],[\"so\",{\"caption\":\"Archivstraße [Dahlem]\",\"key\":\"830\"}],[\"so\",{\"caption\":\"Arcostraße [Charlottenburg]\",\"key\":\"831\"}],[\"so\",{\"caption\":\"Arendsweg [Alt-Hohenschönhausen]\",\"key\":\"832\"}],[\"so\",{\"caption\":\"Arenholzsteig [Tempelhof]\",\"key\":\"833\"}],[\"so\",{\"caption\":\"Argenauer Straße [Köpenick]\",\"key\":\"834\"}],[\"so\",{\"caption\":\"Argentinische Allee [Dahlem, Zehlendorf]\",\"key\":\"835\"}],[\"so\",{\"caption\":\"Argoallee [Schmöckwitz]\",\"key\":\"836\"}],[\"so\",{\"caption\":\"Argonnenweg [Blankenfelde]\",\"key\":\"837\"}],[\"so\",{\"caption\":\"Ariadnestraße [Frohnau]\",\"key\":\"838\"}],[\"so\",{\"caption\":\"Aristide-Briand-Brücke [Tegel]\",\"key\":\"839\"}],[\"so\",{\"caption\":\"Aristotelessteig [Karlshorst]\",\"key\":\"840\"}],[\"so\",{\"caption\":\"Arkenberger Brücke [Blankenfelde]\",\"key\":\"841\"}],[\"so\",{\"caption\":\"Arkenberger Damm [Buch]\",\"key\":\"842\"}],[\"so\",{\"caption\":\"Arkonaplatz [Mitte]\",\"key\":\"843\"}],[\"so\",{\"caption\":\"Arkonastraße [Pankow]\",\"key\":\"844\"}],[\"so\",{\"caption\":\"Armbrustweg [Reinickendorf]\",\"key\":\"845\"}],[\"so\",{\"caption\":\"Armenische Straße [Wedding]\",\"key\":\"846\"}],[\"so\",{\"caption\":\"Arminiusstraße [Moabit]\",\"key\":\"847\"}],[\"so\",{\"caption\":\"Arndtplatz [Adlershof]\",\"key\":\"848\"}],[\"so\",{\"caption\":\"Arndtstraße [Adlershof]\",\"key\":\"849\"}],[\"so\",{\"caption\":\"Arndtstraße [Kaulsdorf, Mahlsdorf]\",\"key\":\"850\"}],[\"so\",{\"caption\":\"Arndtstraße [Kreuzberg]\",\"key\":\"851\"}],[\"so\",{\"caption\":\"Arneburger Straße [Hellersdorf]\",\"key\":\"852\"}],[\"so\",{\"caption\":\"Arnfriedstraße [Biesdorf]\",\"key\":\"853\"}],[\"so\",{\"caption\":\"Arnheidstraße [Hermsdorf]\",\"key\":\"854\"}],[\"so\",{\"caption\":\"Arnikaweg [Buckow, Rudow]\",\"key\":\"855\"}],[\"so\",{\"caption\":\"Arnimallee [Dahlem]\",\"key\":\"856\"}],[\"so\",{\"caption\":\"Arnimplatz [Prenzlauer Berg]\",\"key\":\"857\"}],[\"so\",{\"caption\":\"Arnimstraße [Alt-Hohenschönhausen, Neu-Hohenschönhausen]\",\"key\":\"858\"}],[\"so\",{\"caption\":\"Arno-Holz-Straße [Steglitz]\",\"key\":\"859\"}],[\"so\",{\"caption\":\"Arnold-Knoblauch-Ring [Wannsee]\",\"key\":\"860\"}],[\"so\",{\"caption\":\"Arnold-Schönberg-Platz [Weißensee]\",\"key\":\"861\"}],[\"so\",{\"caption\":\"Arnold-Zweig-Straße [Pankow]\",\"key\":\"862\"}],[\"so\",{\"caption\":\"Arno-Philippsthal-Straße [Biesdorf]\",\"key\":\"863\"}],[\"so\",{\"caption\":\"Arnouxstraße [Französisch Buchholz]\",\"key\":\"864\"}],[\"so\",{\"caption\":\"Arnsberger Straße [Biesdorf]\",\"key\":\"865\"}],[\"so\",{\"caption\":\"Arnstädter Straße [Lankwitz]\",\"key\":\"866\"}],[\"so\",{\"caption\":\"Arnsteinweg [Rosenthal]\",\"key\":\"867\"}],[\"so\",{\"caption\":\"Arnswalder Platz [Prenzlauer Berg]\",\"key\":\"868\"}],[\"so\",{\"caption\":\"Arnulfstraße [Schöneberg, Tempelhof]\",\"key\":\"869\"}],[\"so\",{\"caption\":\"Aronsstraße [Neukölln]\",\"key\":\"870\"}],[\"so\",{\"caption\":\"Aroser Allee [Wedding, Reinickendorf]\",\"key\":\"871\"}],[\"so\",{\"caption\":\"Artemisstraße [Hermsdorf, Waidmannslust]\",\"key\":\"872\"}],[\"so\",{\"caption\":\"Arthur-Müller-Straße [Johannisthal]\",\"key\":\"873\"}],[\"so\",{\"caption\":\"Arthur-Weisbrodt-Straße [Fennpfuhl]\",\"key\":\"874\"}],[\"so\",{\"caption\":\"Arturweg [Mahlsdorf]\",\"key\":\"875\"}],[\"so\",{\"caption\":\"Artuswall [Frohnau]\",\"key\":\"876\"}],[\"so\",{\"caption\":\"Arysallee [Westend]\",\"key\":\"877\"}],[\"so\",{\"caption\":\"Asbestweg [Buckow]\",\"key\":\"878\"}],[\"so\",{\"caption\":\"Aschaffenburger Straße [Lichtenrade]\",\"key\":\"879\"}],[\"so\",{\"caption\":\"Aschaffenburger Straße [Wilmersdorf, Schöneberg]\",\"key\":\"880\"}],[\"so\",{\"caption\":\"Ascheberger Weg [Tegel]\",\"key\":\"881\"}],[\"so\",{\"caption\":\"Aschenbrödelstraße [Köpenick]\",\"key\":\"882\"}],[\"so\",{\"caption\":\"Ascherslebener Weg [Rudow]\",\"key\":\"883\"}],[\"so\",{\"caption\":\"Asgardstraße [Heinersdorf]\",\"key\":\"884\"}],[\"so\",{\"caption\":\"Ashdodstraße [Hakenfelde]\",\"key\":\"885\"}],[\"so\",{\"caption\":\"Askaloner Weg [Frohnau]\",\"key\":\"886\"}],[\"so\",{\"caption\":\"Askanierring [Spandau]\",\"key\":\"887\"}],[\"so\",{\"caption\":\"Askanischer Platz [Kreuzberg]\",\"key\":\"888\"}],[\"so\",{\"caption\":\"Asnièresstraße [Hakenfelde]\",\"key\":\"889\"}],[\"so\",{\"caption\":\"Aspenweg [Hakenfelde]\",\"key\":\"890\"}],[\"so\",{\"caption\":\"Asseburgpfad [Köpenick]\",\"key\":\"891\"}],[\"so\",{\"caption\":\"Aßmannshauser Straße [Wilmersdorf]\",\"key\":\"892\"}],[\"so\",{\"caption\":\"Aßmannstraße [Friedrichshagen]\",\"key\":\"893\"}],[\"so\",{\"caption\":\"Asta-Nielsen-Straße [Pankow]\",\"key\":\"894\"}],[\"so\",{\"caption\":\"Asternplatz [Lichterfelde]\",\"key\":\"895\"}],[\"so\",{\"caption\":\"Astridstraße [Malchow, Wartenberg]\",\"key\":\"896\"}],[\"so\",{\"caption\":\"Atlantisring [Bohnsdorf]\",\"key\":\"897\"}],[\"so\",{\"caption\":\"Attendorner Weg [Tegel]\",\"key\":\"898\"}],[\"so\",{\"caption\":\"Attilagarten [Tempelhof]\",\"key\":\"899\"}],[\"so\",{\"caption\":\"Attilaplatz [Tempelhof]\",\"key\":\"900\"}],[\"so\",{\"caption\":\"Attilastraße [Steglitz, Tempelhof]\",\"key\":\"901\"}],[\"so\",{\"caption\":\"Attinghausenweg [Mahlsdorf]\",\"key\":\"902\"}],[\"so\",{\"caption\":\"Atzpodienstraße [Lichtenberg]\",\"key\":\"903\"}],[\"so\",{\"caption\":\"Auber Steig [Frohnau]\",\"key\":\"904\"}],[\"so\",{\"caption\":\"Aubertstraße [Französisch Buchholz]\",\"key\":\"905\"}],[\"so\",{\"caption\":\"Auerbacher Ring [Hellersdorf]\",\"key\":\"906\"}],[\"so\",{\"caption\":\"Auerbachstraße [Grunewald]\",\"key\":\"907\"}],[\"so\",{\"caption\":\"Auerhahnbalz [Zehlendorf]\",\"key\":\"908\"}],[\"so\",{\"caption\":\"Auersbergstraße [Marzahn]\",\"key\":\"909\"}],[\"so\",{\"caption\":\"Auerstraße [Friedrichshain]\",\"key\":\"910\"}],[\"so\",{\"caption\":\"Auerswaldstraße [Altglienicke]\",\"key\":\"911\"}],[\"so\",{\"caption\":\"Auf dem Grat [Dahlem]\",\"key\":\"912\"}],[\"so\",{\"caption\":\"Auf dem Mühlenberg [Lübars]\",\"key\":\"913\"}],[\"so\",{\"caption\":\"Auf der Höh [Kaulsdorf]\",\"key\":\"914\"}],[\"so\",{\"caption\":\"Auf der Planweide [Buckow]\",\"key\":\"915\"}],[\"so\",{\"caption\":\"Auffacher Weg [Rosenthal]\",\"key\":\"916\"}],[\"so\",{\"caption\":\"Augenfalterstraße [Biesdorf]\",\"key\":\"917\"}],[\"so\",{\"caption\":\"Augsburger Platz [Lichtenrade]\",\"key\":\"918\"}],[\"so\",{\"caption\":\"Augsburger Straße [Charlottenburg, Wilmersdorf, Schöneberg]\",\"key\":\"919\"}],[\"so\",{\"caption\":\"Augsburger Straße [Lichtenrade]\",\"key\":\"920\"}],[\"so\",{\"caption\":\"Augustaplatz [Lichterfelde]\",\"key\":\"921\"}],[\"so\",{\"caption\":\"Augustastraße [Alt-Hohenschönhausen]\",\"key\":\"922\"}],[\"so\",{\"caption\":\"Augustastraße [Lichterfelde]\",\"key\":\"923\"}],[\"so\",{\"caption\":\"Augustaufer [Spandau]\",\"key\":\"924\"}],[\"so\",{\"caption\":\"August-Bebel-Straße [Rahnsdorf]\",\"key\":\"925\"}],[\"so\",{\"caption\":\"August-Bier-Platz [Westend]\",\"key\":\"926\"}],[\"so\",{\"caption\":\"August-Druckenmüller-Brücke [Schöneberg]\",\"key\":\"927\"}],[\"so\",{\"caption\":\"Auguste-Hauschner-Straße [Tiergarten]\",\"key\":\"928\"}],[\"so\",{\"caption\":\"Augustenburger Platz [Wedding]\",\"key\":\"929\"}],[\"so\",{\"caption\":\"Auguste-Piccard-Straße [Kladow]\",\"key\":\"930\"}],[\"so\",{\"caption\":\"August-Euler-Zeile [Kladow]\",\"key\":\"931\"}],[\"so\",{\"caption\":\"Auguste-Viktoria-Allee [Reinickendorf]\",\"key\":\"932\"}],[\"so\",{\"caption\":\"Auguste-Viktoria-Straße [Grunewald, Schmargendorf]\",\"key\":\"933\"}],[\"so\",{\"caption\":\"Auguste-Viktoria-Straße [Hermsdorf]\",\"key\":\"934\"}],[\"so\",{\"caption\":\"August-Froehlich-Straße [Rudow]\",\"key\":\"935\"}],[\"so\",{\"caption\":\"August-Lindemann-Straße [Prenzlauer Berg]\",\"key\":\"936\"}],[\"so\",{\"caption\":\"August-Siebke-Straße [Französisch Buchholz]\",\"key\":\"937\"}],[\"so\",{\"caption\":\"Auguststraße [Kaulsdorf]\",\"key\":\"938\"}],[\"so\",{\"caption\":\"Auguststraße [Lichterfelde]\",\"key\":\"939\"}],[\"so\",{\"caption\":\"Auguststraße [Mitte]\",\"key\":\"940\"}],[\"so\",{\"caption\":\"Aumetzer Weg [Staaken]\",\"key\":\"941\"}],[\"so\",{\"caption\":\"Aumühler Straße [Staaken]\",\"key\":\"942\"}],[\"so\",{\"caption\":\"Auraser Weg [Bohnsdorf]\",\"key\":\"943\"}],[\"so\",{\"caption\":\"Aurikelweg [Biesdorf]\",\"key\":\"944\"}],[\"so\",{\"caption\":\"Aurorafalterweg [Biesdorf]\",\"key\":\"945\"}],[\"so\",{\"caption\":\"Außenringbrücke A 114 [Blankenfelde]\",\"key\":\"946\"}],[\"so\",{\"caption\":\"Außenweg [Gatow]\",\"key\":\"947\"}],[\"so\",{\"caption\":\"Autobahnbrücke (Dorfplatz Bohnsdorf) [Bohnsdorf]\",\"key\":\"948\"}],[\"so\",{\"caption\":\"Autobahnbrücke (S-Bhf Altglienicke) [Altglienicke]\",\"key\":\"949\"}],[\"so\",{\"caption\":\"Autobahnbrücke (Spechtstraße) [Bohnsdorf]\",\"key\":\"950\"}],[\"so\",{\"caption\":\"Autobahnbrücke A113 über B96A [Altglienicke]\",\"key\":\"951\"}],[\"so\",{\"caption\":\"Avenue Charles de Gaulle [Waidmannslust, Wittenau]\",\"key\":\"952\"}],[\"so\",{\"caption\":\"Avenue Jean Mermoz [Tegel]\",\"key\":\"953\"}],[\"so\",{\"caption\":\"Axel-Springer-Straße [Kreuzberg, Mitte]\",\"key\":\"954\"}],[\"so\",{\"caption\":\"Axenstraße [Heinersdorf]\",\"key\":\"955\"}],[\"so\",{\"caption\":\"Azaleenstraße [Köpenick]\",\"key\":\"956\"}],[\"so\",{\"caption\":\"B 96A (Süd) [Altglienicke, Grünau]\",\"key\":\"957\"}],[\"so\",{\"caption\":\"Baaber Steig [Heiligensee]\",\"key\":\"958\"}],[\"so\",{\"caption\":\"Babelsberger Straße [Wilmersdorf]\",\"key\":\"959\"}],[\"so\",{\"caption\":\"Bacharacher Straße [Tempelhof]\",\"key\":\"960\"}],[\"so\",{\"caption\":\"Bachestraße [Friedenau]\",\"key\":\"961\"}],[\"so\",{\"caption\":\"Bachstelzenweg [Dahlem]\",\"key\":\"962\"}],[\"so\",{\"caption\":\"Bachstelzenweg [Rahnsdorf]\",\"key\":\"963\"}],[\"so\",{\"caption\":\"Bachstelzenwegbrücke [Rahnsdorf]\",\"key\":\"964\"}],[\"so\",{\"caption\":\"Bachstraße [Bohnsdorf]\",\"key\":\"965\"}],[\"so\",{\"caption\":\"Bachstraße [Charlottenburg, Hansaviertel]\",\"key\":\"966\"}],[\"so\",{\"caption\":\"Bachstraße [Köpenick]\",\"key\":\"967\"}],[\"so\",{\"caption\":\"Bachstraße [Mahlsdorf]\",\"key\":\"968\"}],[\"so\",{\"caption\":\"Bachwitzer Straße [Köpenick]\",\"key\":\"969\"}],[\"so\",{\"caption\":\"Backbergstraße [Britz]\",\"key\":\"970\"}],[\"so\",{\"caption\":\"Bäckerstraße [Rudow]\",\"key\":\"971\"}],[\"so\",{\"caption\":\"Backnanger Straße [Hermsdorf]\",\"key\":\"972\"}],[\"so\",{\"caption\":\"Badbrücke [Gesundbrunnen]\",\"key\":\"973\"}],[\"so\",{\"caption\":\"Badenallee [Westend]\",\"key\":\"974\"}],[\"so\",{\"caption\":\"Badener Ring [Tempelhof]\",\"key\":\"975\"}],[\"so\",{\"caption\":\"Badener Straße [Mahlsdorf]\",\"key\":\"976\"}],[\"so\",{\"caption\":\"Badensche Straße [Wilmersdorf, Schöneberg]\",\"key\":\"977\"}],[\"so\",{\"caption\":\"Baderseestraße [Grünau]\",\"key\":\"978\"}],[\"so\",{\"caption\":\"Badeweg [Nikolassee]\",\"key\":\"979\"}],[\"so\",{\"caption\":\"Bad-Steben-Straße [Wittenau]\",\"key\":\"980\"}],[\"so\",{\"caption\":\"Badstraße [Gesundbrunnen]\",\"key\":\"981\"}],[\"so\",{\"caption\":\"Badstraßenbrücke [Gesundbrunnen]\",\"key\":\"982\"}],[\"so\",{\"caption\":\"Baedekerweg [Staaken]\",\"key\":\"983\"}],[\"so\",{\"caption\":\"Baerwaldbrücke [Kreuzberg]\",\"key\":\"984\"}],[\"so\",{\"caption\":\"Baerwaldpark [Kreuzberg]\",\"key\":\"985\"}],[\"so\",{\"caption\":\"Baerwaldstraße [Kreuzberg]\",\"key\":\"986\"}],[\"so\",{\"caption\":\"Baggerseestraße [Biesdorf]\",\"key\":\"987\"}],[\"so\",{\"caption\":\"Bahndammbrücke [Tegel]\",\"key\":\"988\"}],[\"so\",{\"caption\":\"Bahnhofbrücke Staaken [Staaken]\",\"key\":\"989\"}],[\"so\",{\"caption\":\"Bahnhofplatz [Hermsdorf]\",\"key\":\"990\"}],[\"so\",{\"caption\":\"Bahnhofsstraßenbrücke [Französisch Buchholz]\",\"key\":\"991\"}],[\"so\",{\"caption\":\"Bahnhofstraße [Alt-Hohenschönhausen]\",\"key\":\"992\"}],[\"so\",{\"caption\":\"Bahnhofstraße [Blankenburg, Französisch Buchholz]\",\"key\":\"993\"}],[\"so\",{\"caption\":\"Bahnhofstraße [Blankenfelde]\",\"key\":\"994\"}],[\"so\",{\"caption\":\"Bahnhofstraße [Karow]\",\"key\":\"995\"}],[\"so\",{\"caption\":\"Bahnhofstraße [Köpenick]\",\"key\":\"996\"}],[\"so\",{\"caption\":\"Bahnhofstraße [Lichtenrade]\",\"key\":\"997\"}],[\"so\",{\"caption\":\"Bahnhofstraße [Lichterfelde]\",\"key\":\"998\"}],[\"so\",{\"caption\":\"Bahnhofstraße [Schöneberg]\",\"key\":\"999\"}],[\"so\",{\"caption\":\"Bahnstraße [Marienfelde]\",\"key\":\"1000\"}],[\"so\",{\"caption\":\"Bahnweg [Altglienicke]\",\"key\":\"1001\"}],[\"so\",{\"caption\":\"Bahrendorfer Straße [Köpenick]\",\"key\":\"1002\"}],[\"so\",{\"caption\":\"Bahrfeldtstraße [Friedrichshain]\",\"key\":\"1003\"}],[\"so\",{\"caption\":\"Baikalstraße [Friedrichsfelde]\",\"key\":\"1004\"}],[\"so\",{\"caption\":\"Bäkebrücke [Lichterfelde, Steglitz]\",\"key\":\"1005\"}],[\"so\",{\"caption\":\"Bäkepark [Steglitz]\",\"key\":\"1006\"}],[\"so\",{\"caption\":\"Bäkestraße [Lichterfelde]\",\"key\":\"1007\"}],[\"so\",{\"caption\":\"Bäkestraße [Wannsee]\",\"key\":\"1008\"}],[\"so\",{\"caption\":\"Balatonstraße [Friedrichsfelde]\",\"key\":\"1009\"}],[\"so\",{\"caption\":\"Balbronner Straße [Dahlem]\",\"key\":\"1010\"}],[\"so\",{\"caption\":\"Baldersheimer Weg [Buckow]\",\"key\":\"1011\"}],[\"so\",{\"caption\":\"Baldurstraße [Rahnsdorf]\",\"key\":\"1012\"}],[\"so\",{\"caption\":\"Ballenstedter Straße [Wilmersdorf]\",\"key\":\"1013\"}],[\"so\",{\"caption\":\"Ballersdorfer Straße [Falkenhagener Feld]\",\"key\":\"1014\"}],[\"so\",{\"caption\":\"Ballinstraße [Britz]\",\"key\":\"1015\"}],[\"so\",{\"caption\":\"Ballonfahrerweg [Schöneberg, Tempelhof]\",\"key\":\"1016\"}],[\"so\",{\"caption\":\"Ballonplatz [Karow]\",\"key\":\"1017\"}],[\"so\",{\"caption\":\"Balsaminenweg [Mahlsdorf]\",\"key\":\"1018\"}],[\"so\",{\"caption\":\"Baltenstraße [Altglienicke]\",\"key\":\"1019\"}],[\"so\",{\"caption\":\"Baltrumstraße [Französisch Buchholz]\",\"key\":\"1020\"}],[\"so\",{\"caption\":\"Baluschekweg [Staaken]\",\"key\":\"1021\"}],[\"so\",{\"caption\":\"Balzerplatz [Biesdorf]\",\"key\":\"1022\"}],[\"so\",{\"caption\":\"Balzerstraße [Biesdorf]\",\"key\":\"1023\"}],[\"so\",{\"caption\":\"Bambachstraße [Neukölln]\",\"key\":\"1024\"}],[\"so\",{\"caption\":\"Bamberger Straße [Lichtenrade]\",\"key\":\"1025\"}],[\"so\",{\"caption\":\"Bamberger Straße [Wilmersdorf, Schöneberg]\",\"key\":\"1026\"}],[\"so\",{\"caption\":\"Bambusweg [Blankenfelde]\",\"key\":\"1027\"}],[\"so\",{\"caption\":\"Bamihlstraße [Hakenfelde]\",\"key\":\"1028\"}],[\"so\",{\"caption\":\"Bananenapfelweg [Französisch Buchholz]\",\"key\":\"1029\"}],[\"so\",{\"caption\":\"Banater Straße [Mahlsdorf]\",\"key\":\"1030\"}],[\"so\",{\"caption\":\"Bandelstraße [Moabit]\",\"key\":\"1031\"}],[\"so\",{\"caption\":\"Bänschstraße [Friedrichshain]\",\"key\":\"1032\"}],[\"so\",{\"caption\":\"Bansiner Straße [Hellersdorf]\",\"key\":\"1033\"}],[\"so\",{\"caption\":\"Bansiner Weg [Lichterfelde]\",\"key\":\"1034\"}],[\"so\",{\"caption\":\"Barbara-McClintock-Straße [Adlershof]\",\"key\":\"1035\"}],[\"so\",{\"caption\":\"Barbarastraße [Lankwitz]\",\"key\":\"1036\"}],[\"so\",{\"caption\":\"Barbarossaplatz [Schöneberg]\",\"key\":\"1037\"}],[\"so\",{\"caption\":\"Barbarossastraße [Wilmersdorf, Schöneberg]\",\"key\":\"1038\"}],[\"so\",{\"caption\":\"Bärbelweg [Konradshöhe]\",\"key\":\"1039\"}],[\"so\",{\"caption\":\"Barbenweg [Köpenick]\",\"key\":\"1040\"}],[\"so\",{\"caption\":\"Barbrücke [Wilmersdorf]\",\"key\":\"1041\"}],[\"so\",{\"caption\":\"Bardelebenweg [Kladow]\",\"key\":\"1042\"}],[\"so\",{\"caption\":\"Bardeyweg [Gatow]\",\"key\":\"1043\"}],[\"so\",{\"caption\":\"Bärdorfer Zeile [Adlershof]\",\"key\":\"1044\"}],[\"so\",{\"caption\":\"Bärenlauchstraße [Niederschöneweide]\",\"key\":\"1045\"}],[\"so\",{\"caption\":\"Bärensteinstraße [Marzahn]\",\"key\":\"1046\"}],[\"so\",{\"caption\":\"Barfusstraße [Wedding]\",\"key\":\"1047\"}],[\"so\",{\"caption\":\"Barkenhof [Nikolassee]\",\"key\":\"1048\"}],[\"so\",{\"caption\":\"Barlachweg [Marienfelde]\",\"key\":\"1049\"}],[\"so\",{\"caption\":\"Barmbeker Weg [Staaken]\",\"key\":\"1050\"}],[\"so\",{\"caption\":\"Barnabasstraße [Tegel]\",\"key\":\"1051\"}],[\"so\",{\"caption\":\"Barnackufer [Lichterfelde]\",\"key\":\"1052\"}],[\"so\",{\"caption\":\"Barnetstraße [Lichtenrade]\",\"key\":\"1053\"}],[\"so\",{\"caption\":\"Barnewitzer Weg [Spandau]\",\"key\":\"1054\"}],[\"so\",{\"caption\":\"Barnhelmstraße [Nikolassee]\",\"key\":\"1055\"}],[\"so\",{\"caption\":\"Barnimplatz [Marzahn]\",\"key\":\"1056\"}],[\"so\",{\"caption\":\"Barnimstraße [Friedrichshain]\",\"key\":\"1057\"}],[\"so\",{\"caption\":\"Barschelplatz [Konradshöhe]\",\"key\":\"1058\"}],[\"so\",{\"caption\":\"Barsekowstraße [Steglitz]\",\"key\":\"1059\"}],[\"so\",{\"caption\":\"Barstraße [Wilmersdorf]\",\"key\":\"1060\"}],[\"so\",{\"caption\":\"Bartastraße [Neukölln]\",\"key\":\"1061\"}],[\"so\",{\"caption\":\"Bartelstraße [Mitte]\",\"key\":\"1062\"}],[\"so\",{\"caption\":\"Barther Straße [Neu-Hohenschönhausen]\",\"key\":\"1063\"}],[\"so\",{\"caption\":\"Barthstraße [Frohnau]\",\"key\":\"1064\"}],[\"so\",{\"caption\":\"Bartningallee [Hansaviertel]\",\"key\":\"1065\"}],[\"so\",{\"caption\":\"Bartschiner Straße [Rudow]\",\"key\":\"1066\"}],[\"so\",{\"caption\":\"Bartschweg [Kladow]\",\"key\":\"1067\"}],[\"so\",{\"caption\":\"Bartzeisigweg [Blankenburg]\",\"key\":\"1068\"}],[\"so\",{\"caption\":\"Baruther Straße [Kreuzberg]\",\"key\":\"1069\"}],[\"so\",{\"caption\":\"Basaltweg [Buckow]\",\"key\":\"1070\"}],[\"so\",{\"caption\":\"Basdorfer Straße [Marzahn]\",\"key\":\"1071\"}],[\"so\",{\"caption\":\"Basdorfer Zeile [Tegel]\",\"key\":\"1072\"}],[\"so\",{\"caption\":\"Baseler Straße [Lichterfelde]\",\"key\":\"1073\"}],[\"so\",{\"caption\":\"Baseler Straße [Reinickendorf]\",\"key\":\"1074\"}],[\"so\",{\"caption\":\"Basilikumweg [Rosenthal]\",\"key\":\"1075\"}],[\"so\",{\"caption\":\"Basiliusweg [Tegel]\",\"key\":\"1076\"}],[\"so\",{\"caption\":\"Bassermannweg [Lichterfelde]\",\"key\":\"1077\"}],[\"so\",{\"caption\":\"Basteiweg [Rosenthal]\",\"key\":\"1078\"}],[\"so\",{\"caption\":\"Bastianstraße [Gesundbrunnen]\",\"key\":\"1079\"}],[\"so\",{\"caption\":\"Battenheimer Weg [Buckow]\",\"key\":\"1080\"}],[\"so\",{\"caption\":\"Bat-Yam-Platz [Gropiusstadt]\",\"key\":\"1081\"}],[\"so\",{\"caption\":\"Bauernheideweg [Rahnsdorf]\",\"key\":\"1082\"}],[\"so\",{\"caption\":\"Bauernweg [Blankenfelde]\",\"key\":\"1083\"}],[\"so\",{\"caption\":\"Bauersfeldzeile [Staaken]\",\"key\":\"1084\"}],[\"so\",{\"caption\":\"Bauerwitzer Weg [Kaulsdorf]\",\"key\":\"1085\"}],[\"so\",{\"caption\":\"Bauführerweg [Britz]\",\"key\":\"1086\"}],[\"so\",{\"caption\":\"Bauhofstraße [Mitte]\",\"key\":\"1087\"}],[\"so\",{\"caption\":\"Bauhüttenweg [Britz]\",\"key\":\"1088\"}],[\"so\",{\"caption\":\"Baumbachstraße [Pankow]\",\"key\":\"1089\"}],[\"so\",{\"caption\":\"Baumeisterstraße [Schöneberg]\",\"key\":\"1090\"}],[\"so\",{\"caption\":\"Bäumerplan [Tempelhof]\",\"key\":\"1091\"}],[\"so\",{\"caption\":\"Baumertweg [Wilhelmstadt]\",\"key\":\"1092\"}],[\"so\",{\"caption\":\"Baumgartensteg [Wilhelmstadt]\",\"key\":\"1093\"}],[\"so\",{\"caption\":\"Baumläuferweg [Buckow, Gropiusstadt]\",\"key\":\"1094\"}],[\"so\",{\"caption\":\"Baummardersteig [Konradshöhe]\",\"key\":\"1095\"}],[\"so\",{\"caption\":\"Baumschulenbrücke (Britz.Zweigkanal) [Baumschulenweg]\",\"key\":\"1096\"}],[\"so\",{\"caption\":\"Baumschulenstraße [Baumschulenweg, Johannisthal]\",\"key\":\"1097\"}],[\"so\",{\"caption\":\"Bausdorfstraße [Kaulsdorf]\",\"key\":\"1098\"}],[\"so\",{\"caption\":\"Bausdorfstraße [Mahlsdorf]\",\"key\":\"1099\"}],[\"so\",{\"caption\":\"Baußnernweg [Marienfelde]\",\"key\":\"1100\"}],[\"so\",{\"caption\":\"Bautzener Platz [Schöneberg]\",\"key\":\"1101\"}],[\"so\",{\"caption\":\"Bautzener Straße [Schöneberg]\",\"key\":\"1102\"}],[\"so\",{\"caption\":\"Bayerischer Platz [Schöneberg]\",\"key\":\"1103\"}],[\"so\",{\"caption\":\"Bayerische Straße [Wilmersdorf]\",\"key\":\"1104\"}],[\"so\",{\"caption\":\"Bayernallee [Westend]\",\"key\":\"1105\"}],[\"so\",{\"caption\":\"Bayernring [Tempelhof]\",\"key\":\"1106\"}],[\"so\",{\"caption\":\"Bayreuther Straße [Schöneberg]\",\"key\":\"1107\"}],[\"so\",{\"caption\":\"Beatestraße [Konradshöhe]\",\"key\":\"1108\"}],[\"so\",{\"caption\":\"Beatrice-Zweig-Straße [Niederschönhausen]\",\"key\":\"1109\"}],[\"so\",{\"caption\":\"Bebelplatz [Mitte]\",\"key\":\"1110\"}],[\"so\",{\"caption\":\"Becherbacher Straße [Müggelheim]\",\"key\":\"1111\"}],[\"so\",{\"caption\":\"Becherweg [Reinickendorf]\",\"key\":\"1112\"}],[\"so\",{\"caption\":\"Bechstedter Weg [Wilmersdorf]\",\"key\":\"1113\"}],[\"so\",{\"caption\":\"Bechsteinweg [Kladow]\",\"key\":\"1114\"}],[\"so\",{\"caption\":\"Beckerstraße [Schöneberg]\",\"key\":\"1115\"}],[\"so\",{\"caption\":\"Beckmannstraße [Lichtenrade]\",\"key\":\"1116\"}],[\"so\",{\"caption\":\"Beckumer Straße [Tegel]\",\"key\":\"1117\"}],[\"so\",{\"caption\":\"Bedeweg [Karow]\",\"key\":\"1118\"}],[\"so\",{\"caption\":\"Beelitzer Weg [Altglienicke]\",\"key\":\"1119\"}],[\"so\",{\"caption\":\"Beerbaumstraße [Karow]\",\"key\":\"1120\"}],[\"so\",{\"caption\":\"Beerenstraße [Zehlendorf]\",\"key\":\"1121\"}],[\"so\",{\"caption\":\"Beerfelder Straße [Karlshorst]\",\"key\":\"1122\"}],[\"so\",{\"caption\":\"Beermannstraße [Alt-Treptow, Plänterwald]\",\"key\":\"1123\"}],[\"so\",{\"caption\":\"Beerwinkel [Falkenhagener Feld]\",\"key\":\"1124\"}],[\"so\",{\"caption\":\"Beeskowdamm [Lichterfelde, Zehlendorf]\",\"key\":\"1125\"}],[\"so\",{\"caption\":\"Beethovenstraße [Biesdorf]\",\"key\":\"1126\"}],[\"so\",{\"caption\":\"Beethovenstraße [Lankwitz]\",\"key\":\"1127\"}],[\"so\",{\"caption\":\"Beethovenstraße [Lichtenrade]\",\"key\":\"1128\"}],[\"so\",{\"caption\":\"Beethovenstraße [Mahlsdorf]\",\"key\":\"1129\"}],[\"so\",{\"caption\":\"Beethovenstraße [Wilhelmsruh]\",\"key\":\"1130\"}],[\"so\",{\"caption\":\"Beetzseeweg [Haselhorst]\",\"key\":\"1131\"}],[\"so\",{\"caption\":\"Begasstraße [Mahlsdorf]\",\"key\":\"1132\"}],[\"so\",{\"caption\":\"Begasstraße [Schöneberg]\",\"key\":\"1133\"}],[\"so\",{\"caption\":\"Begonienplatz [Lichterfelde]\",\"key\":\"1134\"}],[\"so\",{\"caption\":\"Behaimstraße [Charlottenburg]\",\"key\":\"1135\"}],[\"so\",{\"caption\":\"Behaimstraße [Weißensee]\",\"key\":\"1136\"}],[\"so\",{\"caption\":\"Behmstraße [Gesundbrunnen, Prenzlauer Berg]\",\"key\":\"1137\"}],[\"so\",{\"caption\":\"Behmstraßenbrücke [Prenzlauer Berg]\",\"key\":\"1138\"}],[\"so\",{\"caption\":\"Behnitz [Spandau]\",\"key\":\"1139\"}],[\"so\",{\"caption\":\"Behrenstraße [Mitte]\",\"key\":\"1140\"}],[\"so\",{\"caption\":\"Behringstraße [Baumschulenweg]\",\"key\":\"1141\"}],[\"so\",{\"caption\":\"Bei den Wörden [Wittenau]\",\"key\":\"1142\"}],[\"so\",{\"caption\":\"Beiersdorfer Weg [Rahnsdorf]\",\"key\":\"1143\"}],[\"so\",{\"caption\":\"Beifußweg [Rudow]\",\"key\":\"1144\"}],[\"so\",{\"caption\":\"Beilsteiner Straße [Marzahn]\",\"key\":\"1145\"}],[\"so\",{\"caption\":\"Beim Pfarrhof [Staaken]\",\"key\":\"1146\"}],[\"so\",{\"caption\":\"Beizerweg [Rudow]\",\"key\":\"1147\"}],[\"so\",{\"caption\":\"Bekassinenweg [Heiligensee]\",\"key\":\"1148\"}],[\"so\",{\"caption\":\"Belfaster Straße [Wedding]\",\"key\":\"1149\"}],[\"so\",{\"caption\":\"Belforter Straße [Prenzlauer Berg]\",\"key\":\"1150\"}],[\"so\",{\"caption\":\"Bellermannstraße [Gesundbrunnen]\",\"key\":\"1151\"}],[\"so\",{\"caption\":\"Bellevueallee [Tiergarten]\",\"key\":\"1152\"}],[\"so\",{\"caption\":\"Bellevuealleebrücke [Tiergarten]\",\"key\":\"1153\"}],[\"so\",{\"caption\":\"Bellevuebrücke (Erpe) [Köpenick]\",\"key\":\"1154\"}],[\"so\",{\"caption\":\"Bellevuepark [Köpenick]\",\"key\":\"1155\"}],[\"so\",{\"caption\":\"Bellevueparkbrücke [Köpenick]\",\"key\":\"1156\"}],[\"so\",{\"caption\":\"Bellevuestraße [Köpenick]\",\"key\":\"1157\"}],[\"so\",{\"caption\":\"Bellevuestraße [Tiergarten]\",\"key\":\"1158\"}],[\"so\",{\"caption\":\"Bellevue-Ufer [Tiergarten]\",\"key\":\"1159\"}],[\"so\",{\"caption\":\"Bellingstraße [Lankwitz]\",\"key\":\"1160\"}],[\"so\",{\"caption\":\"Belowstraße [Reinickendorf]\",\"key\":\"1161\"}],[\"so\",{\"caption\":\"Belßstraße [Lankwitz, Marienfelde]\",\"key\":\"1162\"}],[\"so\",{\"caption\":\"Belziger Ring [Marzahn]\",\"key\":\"1163\"}],[\"so\",{\"caption\":\"Belziger Straße [Schöneberg]\",\"key\":\"1164\"}],[\"so\",{\"caption\":\"Benatzkyweg [Rudow]\",\"key\":\"1165\"}],[\"so\",{\"caption\":\"Bendastraße [Britz, Neukölln]\",\"key\":\"1166\"}],[\"so\",{\"caption\":\"Bendemannstraße [Adlershof]\",\"key\":\"1167\"}],[\"so\",{\"caption\":\"Bendigstraße [Köpenick]\",\"key\":\"1168\"}],[\"so\",{\"caption\":\"Bendlerbrücke [Tiergarten]\",\"key\":\"1169\"}],[\"so\",{\"caption\":\"Benedicta-Teresia-Weg [Rudow]\",\"key\":\"1170\"}],[\"so\",{\"caption\":\"Benediktinerstraße [Frohnau]\",\"key\":\"1171\"}],[\"so\",{\"caption\":\"Benediktsteinweg [Rosenthal]\",\"key\":\"1172\"}],[\"so\",{\"caption\":\"Benekendorffstraße [Lübars, Waidmannslust]\",\"key\":\"1173\"}],[\"so\",{\"caption\":\"Benfelder Straße [Weißensee]\",\"key\":\"1174\"}],[\"so\",{\"caption\":\"Benfeyweg [Kladow]\",\"key\":\"1175\"}],[\"so\",{\"caption\":\"Ben-Gurion-Straße [Tiergarten]\",\"key\":\"1176\"}],[\"so\",{\"caption\":\"Benjamin-Vogelsdorff-Straße [Pankow]\",\"key\":\"1177\"}],[\"so\",{\"caption\":\"Bennigsenstraße [Friedenau]\",\"key\":\"1178\"}],[\"so\",{\"caption\":\"Bennostraße [Alt-Hohenschönhausen]\",\"key\":\"1179\"}],[\"so\",{\"caption\":\"Benschallee [Nikolassee]\",\"key\":\"1180\"}],[\"so\",{\"caption\":\"Bentschener Weg [Biesdorf]\",\"key\":\"1181\"}],[\"so\",{\"caption\":\"Benzmannstraße [Steglitz]\",\"key\":\"1182\"}],[\"so\",{\"caption\":\"Benzstraße [Marienfelde]\",\"key\":\"1183\"}],[\"so\",{\"caption\":\"Berberitzenweg [Baumschulenweg]\",\"key\":\"1184\"}],[\"so\",{\"caption\":\"Berchtesgadener Straße [Schöneberg]\",\"key\":\"1185\"}],[\"so\",{\"caption\":\"Berenhorststraße [Reinickendorf]\",\"key\":\"1186\"}],[\"so\",{\"caption\":\"Bergaustraße [Plänterwald]\",\"key\":\"1187\"}],[\"so\",{\"caption\":\"Bergedorfer Straße [Kaulsdorf, Mahlsdorf]\",\"key\":\"1188\"}],[\"so\",{\"caption\":\"Bergemannweg [Heiligensee]\",\"key\":\"1189\"}],[\"so\",{\"caption\":\"Bergener Straße [Prenzlauer Berg]\",\"key\":\"1190\"}],[\"so\",{\"caption\":\"Bergengruenstraße [Zehlendorf]\",\"key\":\"1191\"}],[\"so\",{\"caption\":\"Bergfelder Weg [Frohnau]\",\"key\":\"1192\"}],[\"so\",{\"caption\":\"Bergfriedstraße [Kreuzberg]\",\"key\":\"1193\"}],[\"so\",{\"caption\":\"Berghauser Straße [Müggelheim]\",\"key\":\"1194\"}],[\"so\",{\"caption\":\"Bergheimer Platz [Wilmersdorf]\",\"key\":\"1195\"}],[\"so\",{\"caption\":\"Bergheimer Straße [Wilmersdorf]\",\"key\":\"1196\"}],[\"so\",{\"caption\":\"Berghofer Weg [Rahnsdorf]\",\"key\":\"1197\"}],[\"so\",{\"caption\":\"Bergholzstraße [Tempelhof]\",\"key\":\"1198\"}],[\"so\",{\"caption\":\"Bergiusstraße [Neukölln]\",\"key\":\"1199\"}],[\"so\",{\"caption\":\"Bergmannstraße [Kreuzberg]\",\"key\":\"1200\"}],[\"so\",{\"caption\":\"Bergmannstraße [Zehlendorf]\",\"key\":\"1201\"}],[\"so\",{\"caption\":\"Bergrutenpfad [Rosenthal]\",\"key\":\"1202\"}],[\"so\",{\"caption\":\"Bergstraße [Hermsdorf]\",\"key\":\"1203\"}],[\"so\",{\"caption\":\"Bergstraße [Mitte]\",\"key\":\"1204\"}],[\"so\",{\"caption\":\"Bergstraße [Staaken]\",\"key\":\"1205\"}],[\"so\",{\"caption\":\"Bergstraße [Steglitz]\",\"key\":\"1206\"}],[\"so\",{\"caption\":\"Bergstraße [Wannsee]\",\"key\":\"1207\"}],[\"so\",{\"caption\":\"Bergstücker Straße [Wannsee]\",\"key\":\"1208\"}],[\"so\",{\"caption\":\"Berkaer Platz [Schmargendorf]\",\"key\":\"1209\"}],[\"so\",{\"caption\":\"Berkaer Straße [Schmargendorf]\",\"key\":\"1210\"}],[\"so\",{\"caption\":\"Berkenbrücker Steig [Alt-Hohenschönhausen]\",\"key\":\"1211\"}],[\"so\",{\"caption\":\"Berlepschstraße [Zehlendorf]\",\"key\":\"1212\"}],[\"so\",{\"caption\":\"Berlewitzweg [Köpenick]\",\"key\":\"1213\"}],[\"so\",{\"caption\":\"Berlichingenstraße [Moabit]\",\"key\":\"1214\"}],[\"so\",{\"caption\":\"Berliner Allee [Weißensee]\",\"key\":\"1215\"}],[\"so\",{\"caption\":\"Berliner Freiheit [Tiergarten]\",\"key\":\"1216\"}],[\"so\",{\"caption\":\"Berliner Straße [Blankenfelde]\",\"key\":\"1217\"}],[\"so\",{\"caption\":\"Berliner Straße [Dahlem, Zehlendorf]\",\"key\":\"1218\"}],[\"so\",{\"caption\":\"Berliner Straße [Französisch Buchholz]\",\"key\":\"1219\"}],[\"so\",{\"caption\":\"Berliner Straße [Hermsdorf]\",\"key\":\"1220\"}],[\"so\",{\"caption\":\"Berliner Straße [Pankow, Prenzlauer Berg]\",\"key\":\"1221\"}],[\"so\",{\"caption\":\"Berliner Straße [Tegel]\",\"key\":\"1222\"}],[\"so\",{\"caption\":\"Berliner Straße [Wilmersdorf]\",\"key\":\"1223\"}],[\"so\",{\"caption\":\"Berlinickeplatz [Tempelhof]\",\"key\":\"1224\"}],[\"so\",{\"caption\":\"Berlinickestraße [Steglitz]\",\"key\":\"1225\"}],[\"so\",{\"caption\":\"Bernadottestraße [Grunewald, Schmargendorf, Dahlem]\",\"key\":\"1226\"}],[\"so\",{\"caption\":\"Bernauer Straße [Gesundbrunnen, Mitte, Prenzlauer Berg]\",\"key\":\"1227\"}],[\"so\",{\"caption\":\"Bernauer Straße [Lichtenrade]\",\"key\":\"1228\"}],[\"so\",{\"caption\":\"Bernauer Straße [Tegel]\",\"key\":\"1229\"}],[\"so\",{\"caption\":\"Bernburger Straße [Kreuzberg]\",\"key\":\"1230\"}],[\"so\",{\"caption\":\"Bernburger Straße [Marzahn]\",\"key\":\"1231\"}],[\"so\",{\"caption\":\"Bernburger Treppe [Tiergarten]\",\"key\":\"1232\"}],[\"so\",{\"caption\":\"Bernecker Park [Lankwitz]\",\"key\":\"1233\"}],[\"so\",{\"caption\":\"Bernecker Weg [Lankwitz]\",\"key\":\"1234\"}],[\"so\",{\"caption\":\"Berner Straße [Lichterfelde]\",\"key\":\"1235\"}],[\"so\",{\"caption\":\"Bernhard-Bästlein-Straße [Fennpfuhl]\",\"key\":\"1236\"}],[\"so\",{\"caption\":\"Bernhard-Beyer-Straße [Wannsee]\",\"key\":\"1237\"}],[\"so\",{\"caption\":\"Bernhard-Lichtenberg-Platz [Tegel]\",\"key\":\"1238\"}],[\"so\",{\"caption\":\"Bernhard-Lichtenberg-Straße [Charlottenburg-Nord]\",\"key\":\"1239\"}],[\"so\",{\"caption\":\"Bernhard-Lichtenberg-Straße [Prenzlauer Berg]\",\"key\":\"1240\"}],[\"so\",{\"caption\":\"Bernhardsteinweg [Rosenthal]\",\"key\":\"1241\"}],[\"so\",{\"caption\":\"Bernhardstraße [Wilmersdorf]\",\"key\":\"1242\"}],[\"so\",{\"caption\":\"Bernhard-Weiß-Straße [Mitte]\",\"key\":\"1243\"}],[\"so\",{\"caption\":\"Bernhard-Wieck-Promenade [Grunewald]\",\"key\":\"1244\"}],[\"so\",{\"caption\":\"Bernkasteler Straße [Weißensee]\",\"key\":\"1245\"}],[\"so\",{\"caption\":\"Bernkastler Platz [Lankwitz]\",\"key\":\"1246\"}],[\"so\",{\"caption\":\"Bernkastler Straße [Lankwitz]\",\"key\":\"1247\"}],[\"so\",{\"caption\":\"Bernkastler Weg [Hakenfelde]\",\"key\":\"1248\"}],[\"so\",{\"caption\":\"Bernshausener Ring [Wittenau]\",\"key\":\"1249\"}],[\"so\",{\"caption\":\"Bernstadter Weg [Adlershof]\",\"key\":\"1250\"}],[\"so\",{\"caption\":\"Bernsteinring [Buckow]\",\"key\":\"1251\"}],[\"so\",{\"caption\":\"Bernstorffstraße [Tegel]\",\"key\":\"1252\"}],[\"so\",{\"caption\":\"Berntweg [Buckow]\",\"key\":\"1253\"}],[\"so\",{\"caption\":\"Bernulfstraße [Altglienicke]\",\"key\":\"1254\"}],[\"so\",{\"caption\":\"Berolinastraße [Mitte]\",\"key\":\"1255\"}],[\"so\",{\"caption\":\"Bersarinplatz [Friedrichshain]\",\"key\":\"1256\"}],[\"so\",{\"caption\":\"Bertastraße [Alt-Hohenschönhausen]\",\"key\":\"1257\"}],[\"so\",{\"caption\":\"Bertastraße [Hermsdorf]\",\"key\":\"1258\"}],[\"so\",{\"caption\":\"Berta-Waterstradt-Straße [Adlershof]\",\"key\":\"1259\"}],[\"so\",{\"caption\":\"Bertha-Benz-Straße [Moabit]\",\"key\":\"1260\"}],[\"so\",{\"caption\":\"Berthelsdorfer Straße [Neukölln]\",\"key\":\"1261\"}],[\"so\",{\"caption\":\"Berthold-Schwarz-Straße [Haselhorst]\",\"key\":\"1262\"}],[\"so\",{\"caption\":\"Bertholdstraße [Zehlendorf]\",\"key\":\"1263\"}],[\"so\",{\"caption\":\"Bertolt-Brecht-Platz [Mitte]\",\"key\":\"1264\"}],[\"so\",{\"caption\":\"Bertramstraße [Hermsdorf]\",\"key\":\"1265\"}],[\"so\",{\"caption\":\"Bertricher Weg [Hakenfelde]\",\"key\":\"1266\"}],[\"so\",{\"caption\":\"Bertricher Weg [Weißensee]\",\"key\":\"1267\"}],[\"so\",{\"caption\":\"Beruner Straße [Biesdorf]\",\"key\":\"1268\"}],[\"so\",{\"caption\":\"Beselerstraße [Lankwitz]\",\"key\":\"1269\"}],[\"so\",{\"caption\":\"Besenbinderstraße [Altglienicke]\",\"key\":\"1270\"}],[\"so\",{\"caption\":\"Besingweg [Gatow]\",\"key\":\"1271\"}],[\"so\",{\"caption\":\"Beskidenstraße [Nikolassee]\",\"key\":\"1272\"}],[\"so\",{\"caption\":\"Besselstraße [Kreuzberg]\",\"key\":\"1273\"}],[\"so\",{\"caption\":\"Bessemerstraße [Schöneberg]\",\"key\":\"1274\"}],[\"so\",{\"caption\":\"Betckestraße [Wilhelmstadt]\",\"key\":\"1275\"}],[\"so\",{\"caption\":\"Bethaniendamm [Kreuzberg, Mitte]\",\"key\":\"1276\"}],[\"so\",{\"caption\":\"Bethanienpark [Kreuzberg]\",\"key\":\"1277\"}],[\"so\",{\"caption\":\"Bethlehemkirchplatz [Mitte]\",\"key\":\"1278\"}],[\"so\",{\"caption\":\"Bettinastraße [Grunewald]\",\"key\":\"1279\"}],[\"so\",{\"caption\":\"Bettina-von-Arnim-Ufer [Tiergarten]\",\"key\":\"1280\"}],[\"so\",{\"caption\":\"Betty-Hirsch-Platz [Schmargendorf]\",\"key\":\"1281\"}],[\"so\",{\"caption\":\"Betzdorfer Pfad [Tegel]\",\"key\":\"1282\"}],[\"so\",{\"caption\":\"Beuckestraße [Zehlendorf]\",\"key\":\"1283\"}],[\"so\",{\"caption\":\"Beusselbrücke [Moabit]\",\"key\":\"1284\"}],[\"so\",{\"caption\":\"Beusselstraße [Moabit]\",\"key\":\"1285\"}],[\"so\",{\"caption\":\"Beutenweg [Schmöckwitz]\",\"key\":\"1286\"}],[\"so\",{\"caption\":\"Beuthener Straße [Karow]\",\"key\":\"1287\"}],[\"so\",{\"caption\":\"Beuthstraße [Mitte]\",\"key\":\"1288\"}],[\"so\",{\"caption\":\"Beuthstraße [Niederschönhausen]\",\"key\":\"1289\"}],[\"so\",{\"caption\":\"Bevernstraße [Kreuzberg]\",\"key\":\"1290\"}],[\"so\",{\"caption\":\"Beverstedter Weg [Wilmersdorf]\",\"key\":\"1291\"}],[\"so\",{\"caption\":\"Beyerstraße [Wilhelmstadt]\",\"key\":\"1292\"}],[\"so\",{\"caption\":\"Beymestraße [Steglitz]\",\"key\":\"1293\"}],[\"so\",{\"caption\":\"Beyrodtstraße [Marienfelde]\",\"key\":\"1294\"}],[\"so\",{\"caption\":\"Beyschlagstraße [Heiligensee]\",\"key\":\"1295\"}],[\"so\",{\"caption\":\"Biberacher Weg [Steglitz]\",\"key\":\"1296\"}],[\"so\",{\"caption\":\"Biberpelzstraße [Rahnsdorf]\",\"key\":\"1297\"}],[\"so\",{\"caption\":\"Bibersteig [Schmargendorf]\",\"key\":\"1298\"}],[\"so\",{\"caption\":\"Bidenswinkel [Lichtenberg]\",\"key\":\"1299\"}],[\"so\",{\"caption\":\"Biebersdorfer Weg [Schmöckwitz]\",\"key\":\"1300\"}],[\"so\",{\"caption\":\"Biebricher Straße [Neukölln]\",\"key\":\"1301\"}],[\"so\",{\"caption\":\"Biedenkopfer Straße [Tegel]\",\"key\":\"1302\"}],[\"so\",{\"caption\":\"Biedermannweg [Westend]\",\"key\":\"1303\"}],[\"so\",{\"caption\":\"Bielckenweg [Buch]\",\"key\":\"1304\"}],[\"so\",{\"caption\":\"Bielefelder Straße [Wilmersdorf]\",\"key\":\"1305\"}],[\"so\",{\"caption\":\"Bieler Straße [Reinickendorf]\",\"key\":\"1306\"}],[\"so\",{\"caption\":\"Bienenweg [Falkenhagener Feld]\",\"key\":\"1307\"}],[\"so\",{\"caption\":\"Bienwaldring [Buckow]\",\"key\":\"1308\"}],[\"so\",{\"caption\":\"Biesalskistraße [Zehlendorf]\",\"key\":\"1309\"}],[\"so\",{\"caption\":\"Biesdorfer Blumenwiese [Biesdorf]\",\"key\":\"1310\"}],[\"so\",{\"caption\":\"Biesdorfer Friedhofsweg [Biesdorf]\",\"key\":\"1311\"}],[\"so\",{\"caption\":\"Biesdorfer Promenade [Biesdorf]\",\"key\":\"1312\"}],[\"so\",{\"caption\":\"Biesdorfer Weg [Biesdorf, Köpenick]\",\"key\":\"1313\"}],[\"so\",{\"caption\":\"Bieselheider Weg [Frohnau]\",\"key\":\"1314\"}],[\"so\",{\"caption\":\"Biesenbrower Straße [Neu-Hohenschönhausen]\",\"key\":\"1315\"}],[\"so\",{\"caption\":\"Biesenhorster Weg [Karlshorst]\",\"key\":\"1316\"}],[\"so\",{\"caption\":\"Biesentaler Straße [Gesundbrunnen]\",\"key\":\"1317\"}],[\"so\",{\"caption\":\"Biesenthaler Straße [Alt-Hohenschönhausen]\",\"key\":\"1318\"}],[\"so\",{\"caption\":\"Biesestraße [Zehlendorf]\",\"key\":\"1319\"}],[\"so\",{\"caption\":\"Biesheimring [Zehlendorf]\",\"key\":\"1320\"}],[\"so\",{\"caption\":\"Biesterfelder Straße [Alt-Hohenschönhausen]\",\"key\":\"1321\"}],[\"so\",{\"caption\":\"Bietzkestraße [Friedrichsfelde, Rummelsburg]\",\"key\":\"1322\"}],[\"so\",{\"caption\":\"Bifröstweg [Frohnau]\",\"key\":\"1323\"}],[\"so\",{\"caption\":\"Bildhauerweg [Rudow]\",\"key\":\"1324\"}],[\"so\",{\"caption\":\"Billerbecker Weg [Tegel]\",\"key\":\"1325\"}],[\"so\",{\"caption\":\"Billstedter Pfad [Staaken]\",\"key\":\"1326\"}],[\"so\",{\"caption\":\"Billy-Wilder-Promenade [Lichterfelde]\",\"key\":\"1327\"}],[\"so\",{\"caption\":\"Bilsenkrautstraße [Heiligensee]\",\"key\":\"1328\"}],[\"so\",{\"caption\":\"Bilsestraße [Grunewald]\",\"key\":\"1329\"}],[\"so\",{\"caption\":\"Bimssteinweg [Buckow]\",\"key\":\"1330\"}],[\"so\",{\"caption\":\"Binger Straße [Wilmersdorf]\",\"key\":\"1331\"}],[\"so\",{\"caption\":\"Binnendüne [Karlshorst]\",\"key\":\"1332\"}],[\"so\",{\"caption\":\"Binswangersteig [Bohnsdorf]\",\"key\":\"1333\"}],[\"so\",{\"caption\":\"Binzstraße [Pankow]\",\"key\":\"1334\"}],[\"so\",{\"caption\":\"Birger-Forell-Platz [Wilmersdorf]\",\"key\":\"1335\"}],[\"so\",{\"caption\":\"Birkbuschgarten [Steglitz]\",\"key\":\"1336\"}],[\"so\",{\"caption\":\"Birkbuschgartenpark [Steglitz]\",\"key\":\"1337\"}],[\"so\",{\"caption\":\"Birkbuschstraße [Lankwitz, Steglitz]\",\"key\":\"1338\"}],[\"so\",{\"caption\":\"Birkenallee [Biesdorf]\",\"key\":\"1339\"}],[\"so\",{\"caption\":\"Birkenallee [Karlshorst]\",\"key\":\"1340\"}],[\"so\",{\"caption\":\"Birkenallee [Kladow]\",\"key\":\"1341\"}],[\"so\",{\"caption\":\"Birkenallee [Rahnsdorf]\",\"key\":\"1342\"}],[\"so\",{\"caption\":\"Birkenallee [Rosenthal]\",\"key\":\"1343\"}],[\"so\",{\"caption\":\"Birkenknick [Karlshorst]\",\"key\":\"1344\"}],[\"so\",{\"caption\":\"Birkenplatz [Grunewald]\",\"key\":\"1345\"}],[\"so\",{\"caption\":\"Birkensteinweg [Mahlsdorf]\",\"key\":\"1346\"}],[\"so\",{\"caption\":\"Birkenstraße [Bohnsdorf]\",\"key\":\"1347\"}],[\"so\",{\"caption\":\"Birkenstraße [Kaulsdorf]\",\"key\":\"1348\"}],[\"so\",{\"caption\":\"Birkenstraße [Köpenick]\",\"key\":\"1349\"}],[\"so\",{\"caption\":\"Birkenstraße [Moabit]\",\"key\":\"1350\"}],[\"so\",{\"caption\":\"Birkenstraße [Rahnsdorf]\",\"key\":\"1351\"}],[\"so\",{\"caption\":\"Birkenweg [Adlershof]\",\"key\":\"1352\"}],[\"so\",{\"caption\":\"Birkenweg [Bohnsdorf]\",\"key\":\"1353\"}],[\"so\",{\"caption\":\"Birkenweg [Hakenfelde]\",\"key\":\"1354\"}],[\"so\",{\"caption\":\"Birkenwerderstraße [Märkisches Viertel]\",\"key\":\"1355\"}],[\"so\",{\"caption\":\"Birkheidering [Grünau]\",\"key\":\"1356\"}],[\"so\",{\"caption\":\"Birkholzer Weg [Wartenberg]\",\"key\":\"1357\"}],[\"so\",{\"caption\":\"Birkhuhnweg [Buckow]\",\"key\":\"1358\"}],[\"so\",{\"caption\":\"Birkweilerstraße [Müggelheim]\",\"key\":\"1359\"}],[\"so\",{\"caption\":\"Birlingerweg [Kladow]\",\"key\":\"1360\"}],[\"so\",{\"caption\":\"Birnbaumer Straße [Köpenick]\",\"key\":\"1361\"}],[\"so\",{\"caption\":\"Birnbaumer-Straße-Brücke [Köpenick]\",\"key\":\"1362\"}],[\"so\",{\"caption\":\"Birnbaumring [Blankenfelde]\",\"key\":\"1363\"}],[\"so\",{\"caption\":\"Birnenblütenweg [Französisch Buchholz]\",\"key\":\"1364\"}],[\"so\",{\"caption\":\"Birnenpfad [Staaken]\",\"key\":\"1365\"}],[\"so\",{\"caption\":\"Birnenweg [Altglienicke]\",\"key\":\"1366\"}],[\"so\",{\"caption\":\"Birnhornweg [Mariendorf]\",\"key\":\"1367\"}],[\"so\",{\"caption\":\"Bisamstraße [Mahlsdorf]\",\"key\":\"1368\"}],[\"so\",{\"caption\":\"Bischofsgrüner Weg [Lankwitz]\",\"key\":\"1369\"}],[\"so\",{\"caption\":\"Bischofstaler Straße [Biesdorf, Köpenick]\",\"key\":\"1370\"}],[\"so\",{\"caption\":\"Bischweilerstraße [Zehlendorf]\",\"key\":\"1371\"}],[\"so\",{\"caption\":\"Bismarckallee [Grunewald]\",\"key\":\"1372\"}],[\"so\",{\"caption\":\"Bismarckbrücke [Grunewald]\",\"key\":\"1373\"}],[\"so\",{\"caption\":\"Bismarckplatz [Grunewald]\",\"key\":\"1374\"}],[\"so\",{\"caption\":\"Bismarckplatz [Spandau]\",\"key\":\"1375\"}],[\"so\",{\"caption\":\"Bismarcksfelder Brücke (Wuhle) [Biesdorf]\",\"key\":\"1376\"}],[\"so\",{\"caption\":\"Bismarcksfelder Straße [Biesdorf, Kaulsdorf]\",\"key\":\"1377\"}],[\"so\",{\"caption\":\"Bismarckstraße [Charlottenburg]\",\"key\":\"1378\"}],[\"so\",{\"caption\":\"Bismarckstraße [Spandau]\",\"key\":\"1379\"}],[\"so\",{\"caption\":\"Bismarckstraße [Steglitz]\",\"key\":\"1380\"}],[\"so\",{\"caption\":\"Bismarckstraße [Wannsee]\",\"key\":\"1381\"}],[\"so\",{\"caption\":\"Bismarckstraße [Zehlendorf]\",\"key\":\"1382\"}],[\"so\",{\"caption\":\"Bisonweg [Heiligensee]\",\"key\":\"1383\"}],[\"so\",{\"caption\":\"Bissingzeile [Tiergarten]\",\"key\":\"1384\"}],[\"so\",{\"caption\":\"Bistritzer Pfad [Marienfelde]\",\"key\":\"1385\"}],[\"so\",{\"caption\":\"Bitburger Straße [Alt-Hohenschönhausen, Weißensee]\",\"key\":\"1386\"}],[\"so\",{\"caption\":\"Bitscher Straße [Dahlem]\",\"key\":\"1387\"}],[\"so\",{\"caption\":\"Bitterfelder Brücke [Marzahn]\",\"key\":\"1388\"}],[\"so\",{\"caption\":\"Bitterfelder Straße [Marzahn]\",\"key\":\"1389\"}],[\"so\",{\"caption\":\"Bitterfelder Weg [Rudow]\",\"key\":\"1390\"}],[\"so\",{\"caption\":\"Bitterstraße [Dahlem]\",\"key\":\"1391\"}],[\"so\",{\"caption\":\"Bizetstraße [Weißensee]\",\"key\":\"1392\"}],[\"so\",{\"caption\":\"Björnsonstraße [Prenzlauer Berg]\",\"key\":\"1393\"}],[\"so\",{\"caption\":\"Björnsonstraße [Steglitz]\",\"key\":\"1394\"}],[\"so\",{\"caption\":\"Blakenheideweg [Wilhelmstadt]\",\"key\":\"1395\"}],[\"so\",{\"caption\":\"Blanchardstraße [Karow]\",\"key\":\"1396\"}],[\"so\",{\"caption\":\"Blanckertzweg [Lichterfelde]\",\"key\":\"1397\"}],[\"so\",{\"caption\":\"Blankenbergstraße [Friedenau]\",\"key\":\"1398\"}],[\"so\",{\"caption\":\"Blankenburger Chaussee [Blankenburg, Karow]\",\"key\":\"1399\"}],[\"so\",{\"caption\":\"Blankenburger-Laake-Brücke [Blankenburg]\",\"key\":\"1400\"}],[\"so\",{\"caption\":\"Blankenburger Pflasterweg [Malchow, Blankenburg, Stadtrandsiedlung Malchow]\",\"key\":\"1401\"}],[\"so\",{\"caption\":\"Blankenburger Straße [Französisch Buchholz, Niederschönhausen]\",\"key\":\"1402\"}],[\"so\",{\"caption\":\"Blankenburger Straße [Heinersdorf]\",\"key\":\"1403\"}],[\"so\",{\"caption\":\"Blankenburger Weg [Französisch Buchholz]\",\"key\":\"1404\"}],[\"so\",{\"caption\":\"Blankenburger-Weg-Brücke [Französisch Buchholz]\",\"key\":\"1405\"}],[\"so\",{\"caption\":\"Blankeneser Weg [Staaken]\",\"key\":\"1406\"}],[\"so\",{\"caption\":\"Blankenfelder Chaussee [Blankenfelde, Rosenthal]\",\"key\":\"1407\"}],[\"so\",{\"caption\":\"Blankenfelder Chaussee [Lübars]\",\"key\":\"1408\"}],[\"so\",{\"caption\":\"Blankenfelder Straße [Französisch Buchholz]\",\"key\":\"1409\"}],[\"so\",{\"caption\":\"Blankenfelder Straßenbrücke [Blankenfelde]\",\"key\":\"1410\"}],[\"so\",{\"caption\":\"Blankenhainer Straße [Lankwitz]\",\"key\":\"1411\"}],[\"so\",{\"caption\":\"Blankensteinpark [Prenzlauer Berg]\",\"key\":\"1412\"}],[\"so\",{\"caption\":\"Blankensteinweg [Staaken]\",\"key\":\"1413\"}],[\"so\",{\"caption\":\"Blankestraße [Reinickendorf]\",\"key\":\"1414\"}],[\"so\",{\"caption\":\"Blaschkoallee [Britz]\",\"key\":\"1415\"}],[\"so\",{\"caption\":\"Blasewitzer Ring [Staaken, Wilhelmstadt]\",\"key\":\"1416\"}],[\"so\",{\"caption\":\"Bläßhuhnweg [Heiligensee]\",\"key\":\"1417\"}],[\"so\",{\"caption\":\"Bläulingsweg [Westend]\",\"key\":\"1418\"}],[\"so\",{\"caption\":\"Blaumeisenweg [Lichterfelde]\",\"key\":\"1419\"}],[\"so\",{\"caption\":\"Blaurackenweg [Karlshorst]\",\"key\":\"1420\"}],[\"so\",{\"caption\":\"Blausternweg [Mahlsdorf]\",\"key\":\"1421\"}],[\"so\",{\"caption\":\"Blechenstraße [Weißensee]\",\"key\":\"1422\"}],[\"so\",{\"caption\":\"Bleckmannweg [Lichtenberg]\",\"key\":\"1423\"}],[\"so\",{\"caption\":\"Bleibtreustraße [Charlottenburg]\",\"key\":\"1424\"}],[\"so\",{\"caption\":\"Bleicheroder Straße [Pankow]\",\"key\":\"1425\"}],[\"so\",{\"caption\":\"Bleichertstraße [Marienfelde]\",\"key\":\"1426\"}],[\"so\",{\"caption\":\"Bleichröderpark [Pankow]\",\"key\":\"1427\"}],[\"so\",{\"caption\":\"Blenheimstraße [Marzahn]\",\"key\":\"1428\"}],[\"so\",{\"caption\":\"Blesener Zeile [Tegel]\",\"key\":\"1429\"}],[\"so\",{\"caption\":\"Blindschleichengang [Altglienicke]\",\"key\":\"1430\"}],[\"so\",{\"caption\":\"Blissestraße [Wilmersdorf]\",\"key\":\"1431\"}],[\"so\",{\"caption\":\"Blitzenroder Ring [Wittenau]\",\"key\":\"1432\"}],[\"so\",{\"caption\":\"Blochmannstraße [Lichterfelde]\",\"key\":\"1433\"}],[\"so\",{\"caption\":\"Blochplatz [Gesundbrunnen]\",\"key\":\"1434\"}],[\"so\",{\"caption\":\"Blockbrücke [Spandau]\",\"key\":\"1435\"}],[\"so\",{\"caption\":\"Blockdammbrücke [Karlshorst]\",\"key\":\"1436\"}],[\"so\",{\"caption\":\"Blockdammweg [Karlshorst, Rummelsburg]\",\"key\":\"1437\"}],[\"so\",{\"caption\":\"Blohmstraße [Lichtenrade, Marienfelde]\",\"key\":\"1438\"}],[\"so\",{\"caption\":\"Blomberger Weg [Wittenau]\",\"key\":\"1439\"}],[\"so\",{\"caption\":\"Blossiner Straße [Rahnsdorf]\",\"key\":\"1440\"}],[\"so\",{\"caption\":\"Blücherplatz [Kreuzberg]\",\"key\":\"1441\"}],[\"so\",{\"caption\":\"Blücherstraße [Kreuzberg]\",\"key\":\"1442\"}],[\"so\",{\"caption\":\"Blücherstraße [Lichterfelde]\",\"key\":\"1443\"}],[\"so\",{\"caption\":\"Blücherstraße [Zehlendorf]\",\"key\":\"1444\"}],[\"so\",{\"caption\":\"Blumberger Damm [Biesdorf, Marzahn]\",\"key\":\"1445\"}],[\"so\",{\"caption\":\"Blumberger Straße [Mahlsdorf]\",\"key\":\"1446\"}],[\"so\",{\"caption\":\"Blumenbachweg [Marzahn]\",\"key\":\"1447\"}],[\"so\",{\"caption\":\"Blumenstraße [Friedrichshain]\",\"key\":\"1448\"}],[\"so\",{\"caption\":\"Blumenstraße [Spandau]\",\"key\":\"1449\"}],[\"so\",{\"caption\":\"Blumenthalstraße [Niederschönhausen]\",\"key\":\"1450\"}],[\"so\",{\"caption\":\"Blumenthalstraße [Schöneberg]\",\"key\":\"1451\"}],[\"so\",{\"caption\":\"Blumenthalstraße [Tempelhof]\",\"key\":\"1452\"}],[\"so\",{\"caption\":\"Blumenthalstraße [Zehlendorf]\",\"key\":\"1453\"}],[\"so\",{\"caption\":\"Blumenweg [Mariendorf]\",\"key\":\"1454\"}],[\"so\",{\"caption\":\"Blumenwegbrücke (Nordgraben) [Rosenthal]\",\"key\":\"1455\"}],[\"so\",{\"caption\":\"Blumeslake [Rahnsdorf]\",\"key\":\"1456\"}],[\"so\",{\"caption\":\"Blunckstraße [Wittenau]\",\"key\":\"1457\"}],[\"so\",{\"caption\":\"Blütenauer Straße [Biesdorf]\",\"key\":\"1458\"}],[\"so\",{\"caption\":\"Blüthgenstraße [Wilmersdorf]\",\"key\":\"1459\"}],[\"so\",{\"caption\":\"Boberstraße [Neukölln]\",\"key\":\"1460\"}],[\"so\",{\"caption\":\"Boca-Raton-Straße [Hakenfelde]\",\"key\":\"1461\"}],[\"so\",{\"caption\":\"Bocholter Weg [Tegel]\",\"key\":\"1462\"}],[\"so\",{\"caption\":\"Bochumer Straße [Moabit]\",\"key\":\"1463\"}],[\"so\",{\"caption\":\"Böckhstraße [Kreuzberg]\",\"key\":\"1464\"}],[\"so\",{\"caption\":\"Böcklerpark [Kreuzberg]\",\"key\":\"1465\"}],[\"so\",{\"caption\":\"Böcklerstraße [Kreuzberg]\",\"key\":\"1466\"}],[\"so\",{\"caption\":\"Böcklinstraße [Friedrichshain]\",\"key\":\"1467\"}],[\"so\",{\"caption\":\"Böckmannbrücke [Wannsee]\",\"key\":\"1468\"}],[\"so\",{\"caption\":\"Bockmühlenweg [Köpenick]\",\"key\":\"1469\"}],[\"so\",{\"caption\":\"Bocksbartweg [Rudow]\",\"key\":\"1470\"}],[\"so\",{\"caption\":\"Bocksfeldplatz [Wilhelmstadt]\",\"key\":\"1471\"}],[\"so\",{\"caption\":\"Bocksfeldstraße [Wilhelmstadt]\",\"key\":\"1472\"}],[\"so\",{\"caption\":\"Boddinplatz [Neukölln]\",\"key\":\"1473\"}],[\"so\",{\"caption\":\"Boddinstraße [Neukölln]\",\"key\":\"1474\"}],[\"so\",{\"caption\":\"Bodelschwinghstraße [Baumschulenweg]\",\"key\":\"1475\"}],[\"so\",{\"caption\":\"Bodenmaiser Weg [Karlshorst]\",\"key\":\"1476\"}],[\"so\",{\"caption\":\"Bodestraße [Mitte]\",\"key\":\"1477\"}],[\"so\",{\"caption\":\"Bödikersteig [Siemensstadt]\",\"key\":\"1478\"}],[\"so\",{\"caption\":\"Bödikerstraße [Friedrichshain]\",\"key\":\"1479\"}],[\"so\",{\"caption\":\"Bodmerstraße [Lichtenrade]\",\"key\":\"1480\"}],[\"so\",{\"caption\":\"Bodo-Uhse-Straße [Hellersdorf]\",\"key\":\"1481\"}],[\"so\",{\"caption\":\"Boelckestraße [Tempelhof]\",\"key\":\"1482\"}],[\"so\",{\"caption\":\"Boenkestraße [Karow]\",\"key\":\"1483\"}],[\"so\",{\"caption\":\"Boetticherstraße [Dahlem]\",\"key\":\"1484\"}],[\"so\",{\"caption\":\"Bogenstraße [Lichterfelde]\",\"key\":\"1485\"}],[\"so\",{\"caption\":\"Bogenstraße [Rahnsdorf]\",\"key\":\"1486\"}],[\"so\",{\"caption\":\"Bogenstraße [Zehlendorf]\",\"key\":\"1487\"}],[\"so\",{\"caption\":\"Bogotastraße [Zehlendorf]\",\"key\":\"1488\"}],[\"so\",{\"caption\":\"Böhlener Straße [Hellersdorf]\",\"key\":\"1489\"}],[\"so\",{\"caption\":\"Böhmallee [Schmöckwitz]\",\"key\":\"1490\"}],[\"so\",{\"caption\":\"Böhmerwaldweg [Falkenhagener Feld]\",\"key\":\"1491\"}],[\"so\",{\"caption\":\"Böhmischer Platz [Neukölln]\",\"key\":\"1492\"}],[\"so\",{\"caption\":\"Böhmische Straße [Neukölln]\",\"key\":\"1493\"}],[\"so\",{\"caption\":\"Bohm-Schuch-Weg [Gropiusstadt]\",\"key\":\"1494\"}],[\"so\",{\"caption\":\"Bohnsacker Steig [Heiligensee]\",\"key\":\"1495\"}],[\"so\",{\"caption\":\"Bohnsdorfer Chaussee [Altglienicke]\",\"key\":\"1496\"}],[\"so\",{\"caption\":\"Bohnsdorfer Kirchsteig [Bohnsdorf]\",\"key\":\"1497\"}],[\"so\",{\"caption\":\"Bohnsdorfer Straße [Grünau]\",\"key\":\"1498\"}],[\"so\",{\"caption\":\"Bohnsdorfer Weg [Altglienicke]\",\"key\":\"1499\"}],[\"so\",{\"caption\":\"Bohnstedtstraße [Lichtenrade]\",\"key\":\"1500\"}],[\"so\",{\"caption\":\"Bohrauer Pfad [Adlershof]\",\"key\":\"1501\"}],[\"so\",{\"caption\":\"Bohrerzeile [Karow]\",\"key\":\"1502\"}],[\"so\",{\"caption\":\"Boizenburger Straße [Hellersdorf]\",\"key\":\"1503\"}],[\"so\",{\"caption\":\"Bolchener Straße [Zehlendorf]\",\"key\":\"1504\"}],[\"so\",{\"caption\":\"Boleroweg [Rosenthal]\",\"key\":\"1505\"}],[\"so\",{\"caption\":\"Bolivarallee [Westend]\",\"key\":\"1506\"}],[\"so\",{\"caption\":\"Bölkauer Pfad [Heiligensee]\",\"key\":\"1507\"}],[\"so\",{\"caption\":\"Bollersdorfer Weg [Marzahn]\",\"key\":\"1508\"}],[\"so\",{\"caption\":\"Bollestraße [Tegel]\",\"key\":\"1509\"}],[\"so\",{\"caption\":\"Bollmannweg [Wilhelmstadt]\",\"key\":\"1510\"}],[\"so\",{\"caption\":\"Bölschestraße [Friedrichshagen]\",\"key\":\"1511\"}],[\"so\",{\"caption\":\"Bolteweg [Staaken]\",\"key\":\"1512\"}],[\"so\",{\"caption\":\"Boltonstraße [Haselhorst, Siemensstadt]\",\"key\":\"1513\"}],[\"so\",{\"caption\":\"Boltzmannstraße [Dahlem]\",\"key\":\"1514\"}],[\"so\",{\"caption\":\"Bona-Peiser-Weg [Mitte]\",\"key\":\"1515\"}],[\"so\",{\"caption\":\"Bondickstraße [Waidmannslust]\",\"key\":\"1516\"}],[\"so\",{\"caption\":\"Bonhoefferufer [Charlottenburg]\",\"key\":\"1517\"}],[\"so\",{\"caption\":\"Bonifaziusstraße [Tegel]\",\"key\":\"1518\"}],[\"so\",{\"caption\":\"Boninstraße [Lichterfelde]\",\"key\":\"1519\"}],[\"so\",{\"caption\":\"Bonner Straße [Wilmersdorf]\",\"key\":\"1520\"}],[\"so\",{\"caption\":\"Boothstraße [Lichterfelde]\",\"key\":\"1521\"}],[\"so\",{\"caption\":\"Bootsbauerstraße [Friedrichshain]\",\"key\":\"1522\"}],[\"so\",{\"caption\":\"Bootshausweg [Haselhorst]\",\"key\":\"1523\"}],[\"so\",{\"caption\":\"Bopparder Straße [Karlshorst]\",\"key\":\"1524\"}],[\"so\",{\"caption\":\"Boppstraße [Kreuzberg]\",\"key\":\"1525\"}],[\"so\",{\"caption\":\"Boraweg [Lankwitz]\",\"key\":\"1526\"}],[\"so\",{\"caption\":\"Borchertweg [Spandau]\",\"key\":\"1527\"}],[\"so\",{\"caption\":\"Bordeauxstraße [Französisch Buchholz]\",\"key\":\"1528\"}],[\"so\",{\"caption\":\"Borgfelder Steig [Heiligensee]\",\"key\":\"1529\"}],[\"so\",{\"caption\":\"Borggrevestraße [Reinickendorf]\",\"key\":\"1530\"}],[\"so\",{\"caption\":\"Borgmannstraße [Köpenick]\",\"key\":\"1531\"}],[\"so\",{\"caption\":\"Borgsdorfer Straße [Märkisches Viertel]\",\"key\":\"1532\"}],[\"so\",{\"caption\":\"Boris-Pasternak-Weg [Niederschönhausen]\",\"key\":\"1533\"}],[\"so\",{\"caption\":\"Borkener Weg [Tegel]\",\"key\":\"1534\"}],[\"so\",{\"caption\":\"Borkheider Straße [Marzahn]\",\"key\":\"1535\"}],[\"so\",{\"caption\":\"Borkumer Straße [Schmargendorf, Wilmersdorf]\",\"key\":\"1536\"}],[\"so\",{\"caption\":\"Borkumer Straße [Spandau]\",\"key\":\"1537\"}],[\"so\",{\"caption\":\"Borkumstraße [Pankow]\",\"key\":\"1538\"}],[\"so\",{\"caption\":\"Borkzeile [Spandau]\",\"key\":\"1539\"}],[\"so\",{\"caption\":\"Bornaer Straße [Rudow]\",\"key\":\"1540\"}],[\"so\",{\"caption\":\"Bornemannstraße [Gesundbrunnen]\",\"key\":\"1541\"}],[\"so\",{\"caption\":\"Bornepfad [Hermsdorf]\",\"key\":\"1542\"}],[\"so\",{\"caption\":\"Borner Straße [Neu-Hohenschönhausen]\",\"key\":\"1543\"}],[\"so\",{\"caption\":\"Börnestraße [Weißensee]\",\"key\":\"1544\"}],[\"so\",{\"caption\":\"Bornhagenweg [Lichtenrade]\",\"key\":\"1545\"}],[\"so\",{\"caption\":\"Bornholmer Straße [Gesundbrunnen, Prenzlauer Berg]\",\"key\":\"1546\"}],[\"so\",{\"caption\":\"Börnicker Straße [Wilhelmstadt]\",\"key\":\"1547\"}],[\"so\",{\"caption\":\"Bornimer Straße [Halensee]\",\"key\":\"1548\"}],[\"so\",{\"caption\":\"Bornitzstraße [Lichtenberg]\",\"key\":\"1549\"}],[\"so\",{\"caption\":\"Bornsdorfer Straße [Neukölln]\",\"key\":\"1550\"}],[\"so\",{\"caption\":\"Bornstedter Straße [Halensee]\",\"key\":\"1551\"}],[\"so\",{\"caption\":\"Bornstraße [Steglitz, Friedenau]\",\"key\":\"1552\"}],[\"so\",{\"caption\":\"Borodinstraße [Weißensee]\",\"key\":\"1553\"}],[\"so\",{\"caption\":\"Borretschweg [Friedrichshagen]\",\"key\":\"1554\"}],[\"so\",{\"caption\":\"Borsigdamm [Tegel]\",\"key\":\"1555\"}],[\"so\",{\"caption\":\"Borsigdammbrücke [Tegel]\",\"key\":\"1556\"}],[\"so\",{\"caption\":\"Borsigstraße [Mitte]\",\"key\":\"1557\"}],[\"so\",{\"caption\":\"Borsigwalder Weg [Borsigwalde]\",\"key\":\"1558\"}],[\"so\",{\"caption\":\"Borstellstraße [Steglitz]\",\"key\":\"1559\"}],[\"so\",{\"caption\":\"Borussenbrücke [Nikolassee]\",\"key\":\"1560\"}],[\"so\",{\"caption\":\"Borussenstraße [Nikolassee]\",\"key\":\"1561\"}],[\"so\",{\"caption\":\"Borussiastraße [Tempelhof]\",\"key\":\"1562\"}],[\"so\",{\"caption\":\"Boschpoler Straße [Biesdorf]\",\"key\":\"1563\"}],[\"so\",{\"caption\":\"Boschweg [Neukölln]\",\"key\":\"1564\"}],[\"so\",{\"caption\":\"Bösebrücke [Prenzlauer Berg]\",\"key\":\"1565\"}],[\"so\",{\"caption\":\"Bösensteinweg [Mariendorf]\",\"key\":\"1566\"}],[\"so\",{\"caption\":\"Bosepark [Tempelhof]\",\"key\":\"1567\"}],[\"so\",{\"caption\":\"Bosestraße [Tempelhof]\",\"key\":\"1568\"}],[\"so\",{\"caption\":\"Boskoopweg [Marzahn]\",\"key\":\"1569\"}],[\"so\",{\"caption\":\"Bosporusstraße [Mariendorf]\",\"key\":\"1570\"}],[\"so\",{\"caption\":\"Bossestraße [Friedrichshain]\",\"key\":\"1571\"}],[\"so\",{\"caption\":\"Böttcherstraße [Köpenick]\",\"key\":\"1572\"}],[\"so\",{\"caption\":\"Böttgerstraße [Gesundbrunnen]\",\"key\":\"1573\"}],[\"so\",{\"caption\":\"Böttnerstraße [Karow]\",\"key\":\"1574\"}],[\"so\",{\"caption\":\"Bottroper Weg [Tegel]\",\"key\":\"1575\"}],[\"so\",{\"caption\":\"Bötzowstraße [Prenzlauer Berg]\",\"key\":\"1576\"}],[\"so\",{\"caption\":\"Bouchéstraße [Neukölln, Alt-Treptow]\",\"key\":\"1577\"}],[\"so\",{\"caption\":\"Boulevard Kastanienallee [Hellersdorf]\",\"key\":\"1578\"}],[\"so\",{\"caption\":\"Boumannstraße [Hermsdorf]\",\"key\":\"1579\"}],[\"so\",{\"caption\":\"Boveristraße [Adlershof]\",\"key\":\"1580\"}],[\"so\",{\"caption\":\"Boviststraße [Bohnsdorf]\",\"key\":\"1581\"}],[\"so\",{\"caption\":\"Boxberger Brücke [Marzahn]\",\"key\":\"1582\"}],[\"so\",{\"caption\":\"Boxberger Straße [Marzahn]\",\"key\":\"1583\"}],[\"so\",{\"caption\":\"Boxhagener Platz [Friedrichshain]\",\"key\":\"1584\"}],[\"so\",{\"caption\":\"Boxhagener Straße [Friedrichshain]\",\"key\":\"1585\"}],[\"so\",{\"caption\":\"Boyenallee [Westend]\",\"key\":\"1586\"}],[\"so\",{\"caption\":\"Boyenstraße [Mitte]\",\"key\":\"1587\"}],[\"so\",{\"caption\":\"Bozener Straße [Schöneberg]\",\"key\":\"1588\"}],[\"so\",{\"caption\":\"Brabanter Platz [Wilmersdorf]\",\"key\":\"1589\"}],[\"so\",{\"caption\":\"Brabanter Straße [Wilmersdorf]\",\"key\":\"1590\"}],[\"so\",{\"caption\":\"Brachetweg [Mahlsdorf]\",\"key\":\"1591\"}],[\"so\",{\"caption\":\"Brachfelder Straße [Biesdorf]\",\"key\":\"1592\"}],[\"so\",{\"caption\":\"Brachliner Straße [Biesdorf]\",\"key\":\"1593\"}],[\"so\",{\"caption\":\"Brachvogelstraße [Kreuzberg]\",\"key\":\"1594\"}],[\"so\",{\"caption\":\"Brahestraße [Charlottenburg]\",\"key\":\"1595\"}],[\"so\",{\"caption\":\"Brahmsstraße [Grunewald]\",\"key\":\"1596\"}],[\"so\",{\"caption\":\"Brahmsstraße [Lichtenrade]\",\"key\":\"1597\"}],[\"so\",{\"caption\":\"Brahmsstraße [Lichterfelde]\",\"key\":\"1598\"}],[\"so\",{\"caption\":\"Braillestraße [Steglitz]\",\"key\":\"1599\"}],[\"so\",{\"caption\":\"Bramwaldweg [Falkenhagener Feld]\",\"key\":\"1600\"}],[\"so\",{\"caption\":\"Brandaustraße [Marienfelde]\",\"key\":\"1601\"}],[\"so\",{\"caption\":\"Brandenburgische Straße [Steglitz]\",\"key\":\"1602\"}],[\"so\",{\"caption\":\"Brandenburgische Straße [Wilmersdorf]\",\"key\":\"1603\"}],[\"so\",{\"caption\":\"Brandenburgplatz [Köpenick]\",\"key\":\"1604\"}],[\"so\",{\"caption\":\"Brandesstraße [Kreuzberg]\",\"key\":\"1605\"}],[\"so\",{\"caption\":\"Brandorfer Weg [Biesdorf]\",\"key\":\"1606\"}],[\"so\",{\"caption\":\"Brandtstraße [Hermsdorf]\",\"key\":\"1607\"}],[\"so\",{\"caption\":\"Branitzer Karree [Hellersdorf]\",\"key\":\"1608\"}],[\"so\",{\"caption\":\"Branitzer Platz [Westend]\",\"key\":\"1609\"}],[\"so\",{\"caption\":\"Branitzer Straße [Hellersdorf]\",\"key\":\"1610\"}],[\"so\",{\"caption\":\"Brascheweg [Karlshorst]\",\"key\":\"1611\"}],[\"so\",{\"caption\":\"Braschzeile [Wannsee]\",\"key\":\"1612\"}],[\"so\",{\"caption\":\"Brassenpfad [Köpenick]\",\"key\":\"1613\"}],[\"so\",{\"caption\":\"Bratringweg [Falkenhagener Feld]\",\"key\":\"1614\"}],[\"so\",{\"caption\":\"Bratvogelweg [Rosenthal]\",\"key\":\"1615\"}],[\"so\",{\"caption\":\"Brauereihof [Hakenfelde]\",\"key\":\"1616\"}],[\"so\",{\"caption\":\"Brauerplatz [Lichterfelde]\",\"key\":\"1617\"}],[\"so\",{\"caption\":\"Brauerstraße [Lichterfelde]\",\"key\":\"1618\"}],[\"so\",{\"caption\":\"Brauhausstraße [Weißensee]\",\"key\":\"1619\"}],[\"so\",{\"caption\":\"Brauhofstraße [Charlottenburg]\",\"key\":\"1620\"}],[\"so\",{\"caption\":\"Braunbärenweg [Mahlsdorf]\",\"key\":\"1621\"}],[\"so\",{\"caption\":\"Braunellenplatz [Altglienicke]\",\"key\":\"1622\"}],[\"so\",{\"caption\":\"Braunellensteig [Altglienicke]\",\"key\":\"1623\"}],[\"so\",{\"caption\":\"Braunfelsstraße [Lichtenrade]\",\"key\":\"1624\"}],[\"so\",{\"caption\":\"Braunlager Straße [Britz]\",\"key\":\"1625\"}],[\"so\",{\"caption\":\"Braunschweiger Straße [Neukölln]\",\"key\":\"1626\"}],[\"so\",{\"caption\":\"Braunschweiger Ufer [Britz]\",\"key\":\"1627\"}],[\"so\",{\"caption\":\"Braunsdorfstraße [Biesdorf]\",\"key\":\"1628\"}],[\"so\",{\"caption\":\"Brausensteinweg [Rosenthal]\",\"key\":\"1629\"}],[\"so\",{\"caption\":\"Brebacher Weg [Biesdorf]\",\"key\":\"1630\"}],[\"so\",{\"caption\":\"Breckerfelder Pfad [Tegel]\",\"key\":\"1631\"}],[\"so\",{\"caption\":\"Breddiner Weg [Staaken]\",\"key\":\"1632\"}],[\"so\",{\"caption\":\"Bredereckstraße [Kaulsdorf]\",\"key\":\"1633\"}],[\"so\",{\"caption\":\"Bredowstraße [Moabit]\",\"key\":\"1634\"}],[\"so\",{\"caption\":\"Bredtschneiderstraße [Westend]\",\"key\":\"1635\"}],[\"so\",{\"caption\":\"Breestpromenade [Friedrichshagen]\",\"key\":\"1636\"}],[\"so\",{\"caption\":\"Bregenzer Straße [Wilmersdorf]\",\"key\":\"1637\"}],[\"so\",{\"caption\":\"Brehmestraße [Pankow]\",\"key\":\"1638\"}],[\"so\",{\"caption\":\"Brehmstraße [Karlshorst]\",\"key\":\"1639\"}],[\"so\",{\"caption\":\"Breisacher Straße [Dahlem]\",\"key\":\"1640\"}],[\"so\",{\"caption\":\"Breisgauer Straße [Nikolassee, Zehlendorf]\",\"key\":\"1641\"}],[\"so\",{\"caption\":\"Breitachzeile [Tegel]\",\"key\":\"1642\"}],[\"so\",{\"caption\":\"Breite Gasse [Köpenick]\",\"key\":\"1643\"}],[\"so\",{\"caption\":\"Breitehornweg [Gatow, Kladow]\",\"key\":\"1644\"}],[\"so\",{\"caption\":\"Breitenbachplatz [Wilmersdorf, Dahlem, Steglitz]\",\"key\":\"1645\"}],[\"so\",{\"caption\":\"Breitenbachstraße [Borsigwalde]\",\"key\":\"1646\"}],[\"so\",{\"caption\":\"Breitenfelder Straße [Biesdorf]\",\"key\":\"1647\"}],[\"so\",{\"caption\":\"Breitensteinweg [Zehlendorf]\",\"key\":\"1648\"}],[\"so\",{\"caption\":\"Breiter Weg [Johannisthal]\",\"key\":\"1649\"}],[\"so\",{\"caption\":\"Breite Straße [Mitte]\",\"key\":\"1650\"}],[\"so\",{\"caption\":\"Breite Straße [Pankow]\",\"key\":\"1651\"}],[\"so\",{\"caption\":\"Breite Straße [Schmargendorf]\",\"key\":\"1652\"}],[\"so\",{\"caption\":\"Breite Straße [Spandau]\",\"key\":\"1653\"}],[\"so\",{\"caption\":\"Breite Straße [Steglitz]\",\"key\":\"1654\"}],[\"so\",{\"caption\":\"Breitkopfpark [Reinickendorf]\",\"key\":\"1655\"}],[\"so\",{\"caption\":\"Breitkopfstraße [Reinickendorf]\",\"key\":\"1656\"}],[\"so\",{\"caption\":\"Breitscheidplatz [Charlottenburg]\",\"key\":\"1657\"}],[\"so\",{\"caption\":\"Breitunger Weg [Britz, Buckow]\",\"key\":\"1658\"}],[\"so\",{\"caption\":\"Brekowweg [Karlshorst]\",\"key\":\"1659\"}],[\"so\",{\"caption\":\"Bremer Straße [Lichterfelde]\",\"key\":\"1660\"}],[\"so\",{\"caption\":\"Bremer Straße [Mahlsdorf]\",\"key\":\"1661\"}],[\"so\",{\"caption\":\"Bremer Straße [Moabit]\",\"key\":\"1662\"}],[\"so\",{\"caption\":\"Bremer Weg [Tiergarten]\",\"key\":\"1663\"}],[\"so\",{\"caption\":\"Brennerstraße [Pankow]\",\"key\":\"1664\"}],[\"so\",{\"caption\":\"Brentanostraße [Steglitz]\",\"key\":\"1665\"}],[\"so\",{\"caption\":\"Breslauer Platz [Friedenau]\",\"key\":\"1666\"}],[\"so\",{\"caption\":\"Brester Ring [Französisch Buchholz]\",\"key\":\"1667\"}],[\"so\",{\"caption\":\"Bretagneweg [Blankenfelde]\",\"key\":\"1668\"}],[\"so\",{\"caption\":\"Brettnacher Straße [Zehlendorf]\",\"key\":\"1669\"}],[\"so\",{\"caption\":\"Breubergweg [Hakenfelde]\",\"key\":\"1670\"}],[\"so\",{\"caption\":\"Brieger Straße [Lankwitz]\",\"key\":\"1671\"}],[\"so\",{\"caption\":\"Brienner Straße [Wilmersdorf]\",\"key\":\"1672\"}],[\"so\",{\"caption\":\"Brienzer Straße [Wedding, Reinickendorf]\",\"key\":\"1673\"}],[\"so\",{\"caption\":\"Brieselangweg [Hakenfelde]\",\"key\":\"1674\"}],[\"so\",{\"caption\":\"Briesener Weg [Kaulsdorf, Mahlsdorf]\",\"key\":\"1675\"}],[\"so\",{\"caption\":\"Briesestraße [Neukölln]\",\"key\":\"1676\"}],[\"so\",{\"caption\":\"Briesingstraße [Lichtenrade]\",\"key\":\"1677\"}],[\"so\",{\"caption\":\"Brigittenbrücke [Altglienicke]\",\"key\":\"1678\"}],[\"so\",{\"caption\":\"Brigittensteg (Fußgängerbrücke) [Altglienicke]\",\"key\":\"1679\"}],[\"so\",{\"caption\":\"Brigittenstraße [Lankwitz]\",\"key\":\"1680\"}],[\"so\",{\"caption\":\"Brigittenweg [Altglienicke]\",\"key\":\"1681\"}],[\"so\",{\"caption\":\"Briloner Weg [Lichterfelde]\",\"key\":\"1682\"}],[\"so\",{\"caption\":\"Brinkmannstraße [Steglitz]\",\"key\":\"1683\"}],[\"so\",{\"caption\":\"Bristolstraße [Wedding]\",\"key\":\"1684\"}],[\"so\",{\"caption\":\"Brittendorfer Weg [Zehlendorf]\",\"key\":\"1685\"}],[\"so\",{\"caption\":\"Britzer Allee Brücke [Neukölln]\",\"key\":\"1686\"}],[\"so\",{\"caption\":\"Britzer Brücke [Britz]\",\"key\":\"1687\"}],[\"so\",{\"caption\":\"Britzer Damm [Britz]\",\"key\":\"1688\"}],[\"so\",{\"caption\":\"Britzer Garten [Britz, Mariendorf]\",\"key\":\"1689\"}],[\"so\",{\"caption\":\"Britzer Straße [Mariendorf]\",\"key\":\"1690\"}],[\"so\",{\"caption\":\"Britzer Straße [Niederschöneweide]\",\"key\":\"1691\"}],[\"so\",{\"caption\":\"Britzkestraße [Britz, Neukölln]\",\"key\":\"1692\"}],[\"so\",{\"caption\":\"Brixener Straße [Pankow]\",\"key\":\"1693\"}],[\"so\",{\"caption\":\"Brixplatz [Westend]\",\"key\":\"1694\"}],[\"so\",{\"caption\":\"Brockenstraße [Neukölln]\",\"key\":\"1695\"}],[\"so\",{\"caption\":\"Brockenweg [Blankenburg]\",\"key\":\"1696\"}],[\"so\",{\"caption\":\"Brodauer Straße [Kaulsdorf]\",\"key\":\"1697\"}],[\"so\",{\"caption\":\"Brodenbacher Weg [Weißensee]\",\"key\":\"1698\"}],[\"so\",{\"caption\":\"Brodersenstraße [Wittenau]\",\"key\":\"1699\"}],[\"so\",{\"caption\":\"Brodowiner Ring [Marzahn]\",\"key\":\"1700\"}],[\"so\",{\"caption\":\"Brombeerweg [Westend]\",\"key\":\"1701\"}],[\"so\",{\"caption\":\"Bromelienweg [Heinersdorf]\",\"key\":\"1702\"}],[\"so\",{\"caption\":\"Brommystraße [Kreuzberg]\",\"key\":\"1703\"}],[\"so\",{\"caption\":\"Bröndbystraße [Lichterfelde]\",\"key\":\"1704\"}],[\"so\",{\"caption\":\"Brontëweg [Westend]\",\"key\":\"1705\"}],[\"so\",{\"caption\":\"Brook-Taylor-Straße [Adlershof]\",\"key\":\"1706\"}],[\"so\",{\"caption\":\"Brösener Straße [Friedrichshagen]\",\"key\":\"1707\"}],[\"so\",{\"caption\":\"Brosepark Pankow [Niederschönhausen]\",\"key\":\"1708\"}],[\"so\",{\"caption\":\"Brotteroder Straße [Lankwitz]\",\"key\":\"1709\"}],[\"so\",{\"caption\":\"Bruchgrabenweg [Biesdorf]\",\"key\":\"1710\"}],[\"so\",{\"caption\":\"Bruchsaler Straße [Mahlsdorf]\",\"key\":\"1711\"}],[\"so\",{\"caption\":\"Bruchsaler Straße [Wilmersdorf]\",\"key\":\"1712\"}],[\"so\",{\"caption\":\"Bruchwitzstraße [Lankwitz]\",\"key\":\"1713\"}],[\"so\",{\"caption\":\"Brücke A114 Auf-u.Zufahrt [Französisch Buchholz]\",\"key\":\"1714\"}],[\"so\",{\"caption\":\"Brücke A 114 üb. Malchower Weg [Französisch Buchholz]\",\"key\":\"1715\"}],[\"so\",{\"caption\":\"Brücke A 114 über Bahnhofstraße [Französisch Buchholz]\",\"key\":\"1716\"}],[\"so\",{\"caption\":\"Brücke A114 über die Laake [Französisch Buchholz]\",\"key\":\"1717\"}],[\"so\",{\"caption\":\"Brücke A 114 über die Panke [Französisch Buchholz]\",\"key\":\"1718\"}],[\"so\",{\"caption\":\"Brücke Allee der Kosmonauten [Marzahn]\",\"key\":\"1719\"}],[\"so\",{\"caption\":\"Brücke Am Bahndamm (Wuhle) [Köpenick]\",\"key\":\"1720\"}],[\"so\",{\"caption\":\"Brücke am Bürgerpark [Pankow]\",\"key\":\"1721\"}],[\"so\",{\"caption\":\"Brücke Am Falkenberg [Altglienicke]\",\"key\":\"1722\"}],[\"so\",{\"caption\":\"Brücke am Hebammensteig (über A114) [Französisch Buchholz]\",\"key\":\"1723\"}],[\"so\",{\"caption\":\"Brücke am Heiligentalhügel [Westend]\",\"key\":\"1724\"}],[\"so\",{\"caption\":\"Brücke am Neuen Krug [Rahnsdorf]\",\"key\":\"1725\"}],[\"so\",{\"caption\":\"Brücke am Neuen Tor (Panke) [Mitte]\",\"key\":\"1726\"}],[\"so\",{\"caption\":\"Brücke am schwarzen Graben [Wedding]\",\"key\":\"1727\"}],[\"so\",{\"caption\":\"Brücke Am Steinberg [Waidmannslust]\",\"key\":\"1728\"}],[\"so\",{\"caption\":\"Brücke An der Wuhlheide [Oberschöneweide]\",\"key\":\"1729\"}],[\"so\",{\"caption\":\"Brücke Karower Chaussee [Buch]\",\"key\":\"1730\"}],[\"so\",{\"caption\":\"Brücke Nennhauser Damm [Staaken]\",\"key\":\"1731\"}],[\"so\",{\"caption\":\"Brückenstraße [Mitte]\",\"key\":\"1732\"}],[\"so\",{\"caption\":\"Brückenstraße [Niederschöneweide, Oberschöneweide]\",\"key\":\"1733\"}],[\"so\",{\"caption\":\"Brückenstraße [Rahnsdorf]\",\"key\":\"1734\"}],[\"so\",{\"caption\":\"Brückenstraße [Steglitz]\",\"key\":\"1735\"}],[\"so\",{\"caption\":\"Brückenstraßenbrücke [Rahnsdorf]\",\"key\":\"1736\"}],[\"so\",{\"caption\":\"Brücke Straße 50 [Blankenburg]\",\"key\":\"1737\"}],[\"so\",{\"caption\":\"Brücke Straße 538 [Rahnsdorf]\",\"key\":\"1738\"}],[\"so\",{\"caption\":\"Brücke Straße 81 [Altglienicke]\",\"key\":\"1739\"}],[\"so\",{\"caption\":\"Brücke Teilestraße [Tempelhof]\",\"key\":\"1740\"}],[\"so\",{\"caption\":\"Brücke über den Lietzengraben [Buch]\",\"key\":\"1741\"}],[\"so\",{\"caption\":\"Brücke über den Nordgraben [Wittenau]\",\"key\":\"1742\"}],[\"so\",{\"caption\":\"Brücke über die A 10 [Buch]\",\"key\":\"1743\"}],[\"so\",{\"caption\":\"Brücke über die A10 Verb.Buch-Karow [Buch]\",\"key\":\"1744\"}],[\"so\",{\"caption\":\"Brücke über die Panke S-Bahn Buch [Buch]\",\"key\":\"1745\"}],[\"so\",{\"caption\":\"Brücke über die Schönerlinder Straße [Buch]\",\"key\":\"1746\"}],[\"so\",{\"caption\":\"Brücke über Holzhauser Straße [Tegel]\",\"key\":\"1747\"}],[\"so\",{\"caption\":\"Brücke Waidmannsluster Damm [Tegel]\",\"key\":\"1748\"}],[\"so\",{\"caption\":\"Brucknerstraße [Lankwitz]\",\"key\":\"1749\"}],[\"so\",{\"caption\":\"Brüder-Grimm-Gasse [Tiergarten]\",\"key\":\"1750\"}],[\"so\",{\"caption\":\"Brüderstraße [Lichterfelde]\",\"key\":\"1751\"}],[\"so\",{\"caption\":\"Brüderstraße [Mitte]\",\"key\":\"1752\"}],[\"so\",{\"caption\":\"Brüderstraße [Wilhelmstadt]\",\"key\":\"1753\"}],[\"so\",{\"caption\":\"Brüggemannstraße [Schöneberg]\",\"key\":\"1754\"}],[\"so\",{\"caption\":\"Brümmerstraße [Dahlem]\",\"key\":\"1755\"}],[\"so\",{\"caption\":\"Brunhildstraße [Schöneberg]\",\"key\":\"1756\"}],[\"so\",{\"caption\":\"Brunnenkresseweg [Rosenthal]\",\"key\":\"1757\"}],[\"so\",{\"caption\":\"Brunnenplatz [Gesundbrunnen]\",\"key\":\"1758\"}],[\"so\",{\"caption\":\"Brunnenstraße [Gesundbrunnen, Mitte]\",\"key\":\"1759\"}],[\"so\",{\"caption\":\"Brünnhildestraße [Friedenau]\",\"key\":\"1760\"}],[\"so\",{\"caption\":\"Bruno-Apitz-Straße [Buch]\",\"key\":\"1761\"}],[\"so\",{\"caption\":\"Bruno-Bauer-Straße [Neukölln]\",\"key\":\"1762\"}],[\"so\",{\"caption\":\"Bruno-Baum-Straße [Marzahn]\",\"key\":\"1763\"}],[\"so\",{\"caption\":\"Bruno-Bürgel-Weg [Niederschöneweide]\",\"key\":\"1764\"}],[\"so\",{\"caption\":\"Brunolfweg [Altglienicke]\",\"key\":\"1765\"}],[\"so\",{\"caption\":\"Bruno-Möhring-Straße [Marienfelde]\",\"key\":\"1766\"}],[\"so\",{\"caption\":\"Bruno-Taut-Ring [Britz]\",\"key\":\"1767\"}],[\"so\",{\"caption\":\"Bruno-Taut-Straße [Bohnsdorf]\",\"key\":\"1768\"}],[\"so\",{\"caption\":\"Bruno-Walter-Straße [Lankwitz, Lichterfelde]\",\"key\":\"1769\"}],[\"so\",{\"caption\":\"Bruno-Wille-Straße [Friedrichshagen]\",\"key\":\"1770\"}],[\"so\",{\"caption\":\"Brunowplatz [Tegel]\",\"key\":\"1771\"}],[\"so\",{\"caption\":\"Brunowstraße [Tegel]\",\"key\":\"1772\"}],[\"so\",{\"caption\":\"Brunsbütteler Damm [Spandau, Staaken]\",\"key\":\"1773\"}],[\"so\",{\"caption\":\"Brunswickenweg [Buch]\",\"key\":\"1774\"}],[\"so\",{\"caption\":\"Brusebergstraße [Reinickendorf]\",\"key\":\"1775\"}],[\"so\",{\"caption\":\"Brusendorfer Straße [Neukölln]\",\"key\":\"1776\"}],[\"so\",{\"caption\":\"Brussaer Weg [Mariendorf]\",\"key\":\"1777\"}],[\"so\",{\"caption\":\"Brüsseler Straße [Wedding]\",\"key\":\"1778\"}],[\"so\",{\"caption\":\"Buchberger Straße [Lichtenberg]\",\"key\":\"1779\"}],[\"so\",{\"caption\":\"Buchbinderweg [Rudow]\",\"key\":\"1780\"}],[\"so\",{\"caption\":\"Büchenbronner Steig [Lübars, Waidmannslust]\",\"key\":\"1781\"}],[\"so\",{\"caption\":\"Buchenhainer Brücke (Wuhle) [Biesdorf]\",\"key\":\"1782\"}],[\"so\",{\"caption\":\"Buchenhainer Straße [Biesdorf]\",\"key\":\"1783\"}],[\"so\",{\"caption\":\"Buchenstraße [Kaulsdorf]\",\"key\":\"1784\"}],[\"so\",{\"caption\":\"Buchenweg [Hakenfelde]\",\"key\":\"1785\"}],[\"so\",{\"caption\":\"Bucher Chaussee [Karow]\",\"key\":\"1786\"}],[\"so\",{\"caption\":\"Bucher Straße [Französisch Buchholz]\",\"key\":\"1787\"}],[\"so\",{\"caption\":\"Buchfinkweg [Buckow]\",\"key\":\"1788\"}],[\"so\",{\"caption\":\"Buchholzer Brücke [Blankenfelde]\",\"key\":\"1789\"}],[\"so\",{\"caption\":\"Buchholzer Straße [Blankenfelde]\",\"key\":\"1790\"}],[\"so\",{\"caption\":\"Buchholzer Straße [Niederschönhausen]\",\"key\":\"1791\"}],[\"so\",{\"caption\":\"Buchholzer Straße [Prenzlauer Berg]\",\"key\":\"1792\"}],[\"so\",{\"caption\":\"Buchholzweg [Charlottenburg-Nord]\",\"key\":\"1793\"}],[\"so\",{\"caption\":\"Buchhorster Straße [Wilhelmsruh]\",\"key\":\"1794\"}],[\"so\",{\"caption\":\"Büchnerweg [Adlershof]\",\"key\":\"1795\"}],[\"so\",{\"caption\":\"Büchnerweg [Niederschönhausen]\",\"key\":\"1796\"}],[\"so\",{\"caption\":\"Buchsbaumweg [Rudow]\",\"key\":\"1797\"}],[\"so\",{\"caption\":\"Büchsenweg [Reinickendorf]\",\"key\":\"1798\"}],[\"so\",{\"caption\":\"Buchsteinweg [Mariendorf]\",\"key\":\"1799\"}],[\"so\",{\"caption\":\"Buchstraße [Wedding]\",\"key\":\"1800\"}],[\"so\",{\"caption\":\"Buchsweilerstraße [Dahlem]\",\"key\":\"1801\"}],[\"so\",{\"caption\":\"Buchwaldzeile [Gatow]\",\"key\":\"1802\"}],[\"so\",{\"caption\":\"Buckower Chaussee [Lichtenrade, Marienfelde]\",\"key\":\"1803\"}],[\"so\",{\"caption\":\"Buckower Damm [Britz, Buckow]\",\"key\":\"1804\"}],[\"so\",{\"caption\":\"Buckower Ring [Biesdorf]\",\"key\":\"1805\"}],[\"so\",{\"caption\":\"Buckower Weg [Buckow]\",\"key\":\"1806\"}],[\"so\",{\"caption\":\"Budapester Straße [Charlottenburg, Tiergarten]\",\"key\":\"1807\"}],[\"so\",{\"caption\":\"Budapester Weg [Biesdorf]\",\"key\":\"1808\"}],[\"so\",{\"caption\":\"Buddeplatz [Tegel]\",\"key\":\"1809\"}],[\"so\",{\"caption\":\"Buddestraße [Niederschönhausen]\",\"key\":\"1810\"}],[\"so\",{\"caption\":\"Buddestraße [Tegel]\",\"key\":\"1811\"}],[\"so\",{\"caption\":\"Buddestrassenbrücke [Tegel]\",\"key\":\"1812\"}],[\"so\",{\"caption\":\"Büdnerring [Reinickendorf]\",\"key\":\"1813\"}],[\"so\",{\"caption\":\"Budsiner Straße [Biesdorf]\",\"key\":\"1814\"}],[\"so\",{\"caption\":\"Bugenhagenstraße [Moabit]\",\"key\":\"1815\"}],[\"so\",{\"caption\":\"Buggenhagenstraße [Fennpfuhl]\",\"key\":\"1816\"}],[\"so\",{\"caption\":\"Buggestraße [Steglitz]\",\"key\":\"1817\"}],[\"so\",{\"caption\":\"Bühler Weg [Buckow]\",\"key\":\"1818\"}],[\"so\",{\"caption\":\"Bühringstraße [Weißensee]\",\"key\":\"1819\"}],[\"so\",{\"caption\":\"Buhrowstraße [Steglitz]\",\"key\":\"1820\"}],[\"so\",{\"caption\":\"Bukesweg [Köpenick]\",\"key\":\"1821\"}],[\"so\",{\"caption\":\"Bulgarische Straße [Alt-Treptow, Plänterwald]\",\"key\":\"1822\"}],[\"so\",{\"caption\":\"Bulgenbachweg [Frohnau]\",\"key\":\"1823\"}],[\"so\",{\"caption\":\"Büllinger Straße [Kaulsdorf]\",\"key\":\"1824\"}],[\"so\",{\"caption\":\"Bülowstraße [Kreuzberg, Schöneberg]\",\"key\":\"1825\"}],[\"so\",{\"caption\":\"Bülowstraße [Zehlendorf]\",\"key\":\"1826\"}],[\"so\",{\"caption\":\"Bültenring [Biesdorf]\",\"key\":\"1827\"}],[\"so\",{\"caption\":\"Bumpfuhlpark [Heiligensee]\",\"key\":\"1828\"}],[\"so\",{\"caption\":\"Bundenbacher Weg [Weißensee]\",\"key\":\"1829\"}],[\"so\",{\"caption\":\"Bundesallee [Wilmersdorf, Friedenau]\",\"key\":\"1830\"}],[\"so\",{\"caption\":\"Bundesplatz [Wilmersdorf]\",\"key\":\"1831\"}],[\"so\",{\"caption\":\"Bundesratufer [Moabit]\",\"key\":\"1832\"}],[\"so\",{\"caption\":\"Bundesring [Tempelhof]\",\"key\":\"1833\"}],[\"so\",{\"caption\":\"Bundesstraße 2 [Malchow, Wartenberg, Stadtrandsiedlung Malchow]\",\"key\":\"1834\"}],[\"so\",{\"caption\":\"Bundschuhweg [Frohnau]\",\"key\":\"1835\"}],[\"so\",{\"caption\":\"Bunsenstraße [Mitte]\",\"key\":\"1836\"}],[\"so\",{\"caption\":\"Buntspechtstraße [Konradshöhe]\",\"key\":\"1837\"}],[\"so\",{\"caption\":\"Buntsteinweg [Rosenthal]\",\"key\":\"1838\"}],[\"so\",{\"caption\":\"Buntzelstraße [Bohnsdorf]\",\"key\":\"1839\"}],[\"so\",{\"caption\":\"Bunzlauer Straße [Karow]\",\"key\":\"1840\"}],[\"so\",{\"caption\":\"Buolstraße [Siemensstadt]\",\"key\":\"1841\"}],[\"so\",{\"caption\":\"Burbacher Weg [Falkenhagener Feld]\",\"key\":\"1842\"}],[\"so\",{\"caption\":\"Burchardstraße [Tempelhof]\",\"key\":\"1843\"}],[\"so\",{\"caption\":\"Burgemeisterstraße [Tempelhof]\",\"key\":\"1844\"}],[\"so\",{\"caption\":\"Bürgerheimstraße [Lichtenberg]\",\"key\":\"1845\"}],[\"so\",{\"caption\":\"Bürgerpark Marzahn [Marzahn]\",\"key\":\"1846\"}],[\"so\",{\"caption\":\"Bürgerpark Pankow [Pankow]\",\"key\":\"1847\"}],[\"so\",{\"caption\":\"Bürgersruh [Lübars]\",\"key\":\"1848\"}],[\"so\",{\"caption\":\"Bürgerstraße [Britz]\",\"key\":\"1849\"}],[\"so\",{\"caption\":\"Bürgerstraße [Reinickendorf]\",\"key\":\"1850\"}],[\"so\",{\"caption\":\"Burgfrauenstraße [Frohnau, Hermsdorf]\",\"key\":\"1851\"}],[\"so\",{\"caption\":\"Burggrafenstraße [Kaulsdorf, Mahlsdorf]\",\"key\":\"1852\"}],[\"so\",{\"caption\":\"Burggrafenstraße [Tiergarten]\",\"key\":\"1853\"}],[\"so\",{\"caption\":\"Burghardweg [Biesdorf]\",\"key\":\"1854\"}],[\"so\",{\"caption\":\"Burgherrenstraße [Tempelhof]\",\"key\":\"1855\"}],[\"so\",{\"caption\":\"Bürgipfad [Lichterfelde]\",\"key\":\"1856\"}],[\"so\",{\"caption\":\"Burgsdorfstraße [Wedding]\",\"key\":\"1857\"}],[\"so\",{\"caption\":\"Burgstraße [Mitte]\",\"key\":\"1858\"}],[\"so\",{\"caption\":\"Burgunder Straße [Nikolassee]\",\"key\":\"1859\"}],[\"so\",{\"caption\":\"Burgunder Straße [Wilmersdorf]\",\"key\":\"1860\"}],[\"so\",{\"caption\":\"Burgwallsteg [Spandau]\",\"key\":\"1861\"}],[\"so\",{\"caption\":\"Burgwallstraße [Blankenburg]\",\"key\":\"1862\"}],[\"so\",{\"caption\":\"Buriger Weg [Rahnsdorf]\",\"key\":\"1863\"}],[\"so\",{\"caption\":\"Bürknersfelder Straße [Alt-Hohenschönhausen]\",\"key\":\"1864\"}],[\"so\",{\"caption\":\"Bürknerstraße [Neukölln]\",\"key\":\"1865\"}],[\"so\",{\"caption\":\"Burscheider Weg [Haselhorst]\",\"key\":\"1866\"}],[\"so\",{\"caption\":\"Bürstadter Weg [Zehlendorf]\",\"key\":\"1867\"}],[\"so\",{\"caption\":\"Buschallee [Weißensee]\",\"key\":\"1868\"}],[\"so\",{\"caption\":\"Buschgrabenbrücke [Zehlendorf]\",\"key\":\"1869\"}],[\"so\",{\"caption\":\"Buschgrabenweg [Zehlendorf]\",\"key\":\"1870\"}],[\"so\",{\"caption\":\"Buschhüttener Weg [Falkenhagener Feld]\",\"key\":\"1871\"}],[\"so\",{\"caption\":\"Buschiner Straße [Biesdorf]\",\"key\":\"1872\"}],[\"so\",{\"caption\":\"Büschingstraße [Friedrichshain]\",\"key\":\"1873\"}],[\"so\",{\"caption\":\"Buschkrugallee [Britz]\",\"key\":\"1874\"}],[\"so\",{\"caption\":\"Buschkrugbrücke [Britz]\",\"key\":\"1875\"}],[\"so\",{\"caption\":\"Buschower Weg [Staaken]\",\"key\":\"1876\"}],[\"so\",{\"caption\":\"Buschrosenplatz [Britz]\",\"key\":\"1877\"}],[\"so\",{\"caption\":\"Buschrosensteig [Britz]\",\"key\":\"1878\"}],[\"so\",{\"caption\":\"Buschsperlingweg [Französisch Buchholz]\",\"key\":\"1879\"}],[\"so\",{\"caption\":\"Buschwindröschenweg [Bohnsdorf]\",\"key\":\"1880\"}],[\"so\",{\"caption\":\"Büsingstraße [Friedenau]\",\"key\":\"1881\"}],[\"so\",{\"caption\":\"Busonistraße [Karow]\",\"key\":\"1882\"}],[\"so\",{\"caption\":\"Bussardsteig [Dahlem]\",\"key\":\"1883\"}],[\"so\",{\"caption\":\"Busseallee [Zehlendorf]\",\"key\":\"1884\"}],[\"so\",{\"caption\":\"Büssower Weg [Heiligensee]\",\"key\":\"1885\"}],[\"so\",{\"caption\":\"Büsumer Pfad [Heiligensee]\",\"key\":\"1886\"}],[\"so\",{\"caption\":\"Bütower Straße [Mahlsdorf]\",\"key\":\"1887\"}],[\"so\",{\"caption\":\"Buttenstedtweg [Friedrichshagen]\",\"key\":\"1888\"}],[\"so\",{\"caption\":\"Butterblumensteig [Mahlsdorf]\",\"key\":\"1889\"}],[\"so\",{\"caption\":\"Buttmannstraße [Gesundbrunnen]\",\"key\":\"1890\"}],[\"so\",{\"caption\":\"Büxensteinallee [Grünau]\",\"key\":\"1891\"}],[\"so\",{\"caption\":\"Byronweg [Westend]\",\"key\":\"1892\"}],[\"so\",{\"caption\":\"Cafeastraße [Britz]\",\"key\":\"1893\"}],[\"so\",{\"caption\":\"Cajamarcaplatz [Niederschöneweide]\",\"key\":\"1894\"}],[\"so\",{\"caption\":\"Calandrelli-Anlage [Tiergarten]\",\"key\":\"1895\"}],[\"so\",{\"caption\":\"Calandrellistraße [Lankwitz]\",\"key\":\"1896\"}],[\"so\",{\"caption\":\"Calauer Straße [Märkisches Viertel]\",\"key\":\"1897\"}],[\"so\",{\"caption\":\"Caligariplatz [Weißensee]\",\"key\":\"1898\"}],[\"so\",{\"caption\":\"Calvinstraße [Hermsdorf]\",\"key\":\"1899\"}],[\"so\",{\"caption\":\"Calvinstraße [Moabit]\",\"key\":\"1900\"}],[\"so\",{\"caption\":\"Cambridger Straße [Wedding]\",\"key\":\"1901\"}],[\"so\",{\"caption\":\"Campestraße [Tegel]\",\"key\":\"1902\"}],[\"so\",{\"caption\":\"Camphausenstraße [Zehlendorf]\",\"key\":\"1903\"}],[\"so\",{\"caption\":\"Canovastraße [Schöneberg]\",\"key\":\"1904\"}],[\"so\",{\"caption\":\"Cantalweg [Blankenfelde]\",\"key\":\"1905\"}],[\"so\",{\"caption\":\"Cantianstraße [Prenzlauer Berg]\",\"key\":\"1906\"}],[\"so\",{\"caption\":\"Cantorsteig [Mariendorf]\",\"key\":\"1907\"}],[\"so\",{\"caption\":\"Caprivibrücke [Charlottenburg]\",\"key\":\"1908\"}],[\"so\",{\"caption\":\"Cardinalplatz [Köpenick]\",\"key\":\"1909\"}],[\"so\",{\"caption\":\"Cardinalstraße [Köpenick]\",\"key\":\"1910\"}],[\"so\",{\"caption\":\"Carionweg [Halensee]\",\"key\":\"1911\"}],[\"so\",{\"caption\":\"Carl-Heinrich-Becker-Weg [Steglitz]\",\"key\":\"1912\"}],[\"so\",{\"caption\":\"Carl-Herz-Park [Kreuzberg]\",\"key\":\"1913\"}],[\"so\",{\"caption\":\"Carl-Herz-Ufer [Kreuzberg]\",\"key\":\"1914\"}],[\"so\",{\"caption\":\"Carlo-Schmid-Platz [Wilhelmstadt]\",\"key\":\"1915\"}],[\"so\",{\"caption\":\"Carl-Scheele-Straße [Adlershof]\",\"key\":\"1916\"}],[\"so\",{\"caption\":\"Carl-Schurz-Brücke [Spandau]\",\"key\":\"1917\"}],[\"so\",{\"caption\":\"Carl-Schurz-Straße [Spandau]\",\"key\":\"1918\"}],[\"so\",{\"caption\":\"Carl-Steffeck-Straße [Lichtenrade]\",\"key\":\"1919\"}],[\"so\",{\"caption\":\"Carl-von-Ossietzky-Park [Moabit]\",\"key\":\"1920\"}],[\"so\",{\"caption\":\"Carl-Weder-Park [Britz]\",\"key\":\"1921\"}],[\"so\",{\"caption\":\"Carl-Zuckmayer-Brücke [Schöneberg]\",\"key\":\"1922\"}],[\"so\",{\"caption\":\"Carmerplatz [Steglitz]\",\"key\":\"1923\"}],[\"so\",{\"caption\":\"Carmerstraße [Charlottenburg]\",\"key\":\"1924\"}],[\"so\",{\"caption\":\"Carnotstraße [Charlottenburg]\",\"key\":\"1925\"}],[\"so\",{\"caption\":\"Carola-Neher-Straße [Hellersdorf]\",\"key\":\"1926\"}],[\"so\",{\"caption\":\"Caroline-Herschel-Platz [Friedrichshain]\",\"key\":\"1927\"}],[\"so\",{\"caption\":\"Caroline-Michaelis-Straße [Mitte]\",\"key\":\"1928\"}],[\"so\",{\"caption\":\"Caroline-Tübbecke-Ufer [Friedrichshain]\",\"key\":\"1929\"}],[\"so\",{\"caption\":\"Caroline-von-Humboldt-Weg [Mitte]\",\"key\":\"1930\"}],[\"so\",{\"caption\":\"Carossastraße [Hakenfelde]\",\"key\":\"1931\"}],[\"so\",{\"caption\":\"Carpinusweg [Dahlem]\",\"key\":\"1932\"}],[\"so\",{\"caption\":\"Carstennstraße [Lichterfelde]\",\"key\":\"1933\"}],[\"so\",{\"caption\":\"Caseler Straße [Weißensee]\",\"key\":\"1934\"}],[\"so\",{\"caption\":\"Caspar-Theyß-Straße [Grunewald, Schmargendorf]\",\"key\":\"1935\"}],[\"so\",{\"caption\":\"Cassinohof [Zehlendorf]\",\"key\":\"1936\"}],[\"so\",{\"caption\":\"Catostraße [Mariendorf]\",\"key\":\"1937\"}],[\"so\",{\"caption\":\"Cauerstraße [Charlottenburg]\",\"key\":\"1938\"}],[\"so\",{\"caption\":\"Cautiusstraße [Hakenfelde]\",\"key\":\"1939\"}],[\"so\",{\"caption\":\"Cecilienallee [Hermsdorf]\",\"key\":\"1940\"}],[\"so\",{\"caption\":\"Cecilienbrücke [Hellersdorf]\",\"key\":\"1941\"}],[\"so\",{\"caption\":\"Ceciliengärten [Schöneberg]\",\"key\":\"1942\"}],[\"so\",{\"caption\":\"Cecilienplatz [Hellersdorf]\",\"key\":\"1943\"}],[\"so\",{\"caption\":\"Cecilienplatz [Hermsdorf]\",\"key\":\"1944\"}],[\"so\",{\"caption\":\"Cecilienstraße [Biesdorf, Hellersdorf, Marzahn]\",\"key\":\"1945\"}],[\"so\",{\"caption\":\"Cecilienstraße [Lankwitz]\",\"key\":\"1946\"}],[\"so\",{\"caption\":\"Cecilienstraße [Lichtenrade]\",\"key\":\"1947\"}],[\"so\",{\"caption\":\"Cecilienstraßenbrücke [Hellersdorf]\",\"key\":\"1948\"}],[\"so\",{\"caption\":\"Cedernstraße [Köpenick]\",\"key\":\"1949\"}],[\"so\",{\"caption\":\"Celsiusstraße [Lichterfelde]\",\"key\":\"1950\"}],[\"so\",{\"caption\":\"Centweg [Rosenthal]\",\"key\":\"1951\"}],[\"so\",{\"caption\":\"Cevennenstraße [Französisch Buchholz]\",\"key\":\"1952\"}],[\"so\",{\"caption\":\"Chamierstraße [Alt-Hohenschönhausen]\",\"key\":\"1953\"}],[\"so\",{\"caption\":\"Chamissoplatz [Kreuzberg]\",\"key\":\"1954\"}],[\"so\",{\"caption\":\"Chamissostraße [Französisch Buchholz]\",\"key\":\"1955\"}],[\"so\",{\"caption\":\"Chamissostraße [Hakenfelde]\",\"key\":\"1956\"}],[\"so\",{\"caption\":\"Champagneweg [Blankenfelde]\",\"key\":\"1957\"}],[\"so\",{\"caption\":\"Champignonstraße [Bohnsdorf]\",\"key\":\"1958\"}],[\"so\",{\"caption\":\"Chantiéweg [Französisch Buchholz]\",\"key\":\"1959\"}],[\"so\",{\"caption\":\"Charitéplatz [Mitte]\",\"key\":\"1960\"}],[\"so\",{\"caption\":\"Charitéstraße [Mitte]\",\"key\":\"1961\"}],[\"so\",{\"caption\":\"Charles-Corcelle-Ring [Wedding]\",\"key\":\"1962\"}],[\"so\",{\"caption\":\"Charles-H.-King-Straße [Nikolassee]\",\"key\":\"1963\"}],[\"so\",{\"caption\":\"Charles-Lindbergh-Straße [Kladow]\",\"key\":\"1964\"}],[\"so\",{\"caption\":\"Charlotte-E.-Pauly-Straße [Friedrichshagen]\",\"key\":\"1965\"}],[\"so\",{\"caption\":\"Charlottenbrücke [Spandau]\",\"key\":\"1966\"}],[\"so\",{\"caption\":\"Charlottenbrunner Straße [Schmargendorf]\",\"key\":\"1967\"}],[\"so\",{\"caption\":\"Charlottenburger Brücke [Charlottenburg]\",\"key\":\"1968\"}],[\"so\",{\"caption\":\"Charlottenburger Chaussee [Westend, Spandau, Wilhelmstadt]\",\"key\":\"1969\"}],[\"so\",{\"caption\":\"Charlottenburger Straße [Weißensee]\",\"key\":\"1970\"}],[\"so\",{\"caption\":\"Charlottenburger Straße [Zehlendorf]\",\"key\":\"1971\"}],[\"so\",{\"caption\":\"Charlottenburger Ufer [Charlottenburg]\",\"key\":\"1972\"}],[\"so\",{\"caption\":\"Charlottenstraße [Biesdorf]\",\"key\":\"1973\"}],[\"so\",{\"caption\":\"Charlottenstraße [Friedrichsfelde]\",\"key\":\"1974\"}],[\"so\",{\"caption\":\"Charlottenstraße [Köpenick]\",\"key\":\"1975\"}],[\"so\",{\"caption\":\"Charlottenstraße [Kreuzberg, Mitte]\",\"key\":\"1976\"}],[\"so\",{\"caption\":\"Charlottenstraße [Lankwitz]\",\"key\":\"1977\"}],[\"so\",{\"caption\":\"Charlottenstraße [Lichtenrade]\",\"key\":\"1978\"}],[\"so\",{\"caption\":\"Charlottenstraße [Niederschönhausen, Rosenthal]\",\"key\":\"1979\"}],[\"so\",{\"caption\":\"Charlottenstraße [Spandau]\",\"key\":\"1980\"}],[\"so\",{\"caption\":\"Charlottenstraße [Wannsee]\",\"key\":\"1981\"}],[\"so\",{\"caption\":\"Charlotte-Salomon-Hain [Rummelsburg]\",\"key\":\"1982\"}],[\"so\",{\"caption\":\"Chartronstraße [Französisch Buchholz]\",\"key\":\"1983\"}],[\"so\",{\"caption\":\"Chausseestraße [Gesundbrunnen, Mitte, Wedding]\",\"key\":\"1984\"}],[\"so\",{\"caption\":\"Chausseestraße [Wannsee]\",\"key\":\"1985\"}],[\"so\",{\"caption\":\"Chausseestraßenbrücke [Wedding]\",\"key\":\"1986\"}],[\"so\",{\"caption\":\"Chemnitzer Straße [Kaulsdorf]\",\"key\":\"1987\"}],[\"so\",{\"caption\":\"Cheruskerpark [Schöneberg]\",\"key\":\"1988\"}],[\"so\",{\"caption\":\"Cheruskerstraße [Schöneberg]\",\"key\":\"1989\"}],[\"so\",{\"caption\":\"Chiemseestraße [Grünau]\",\"key\":\"1990\"}],[\"so\",{\"caption\":\"Chiliweg [Rosenthal]\",\"key\":\"1991\"}],[\"so\",{\"caption\":\"Chlodwigstraße [Tempelhof]\",\"key\":\"1992\"}],[\"so\",{\"caption\":\"Chlumer Straße [Lichterfelde]\",\"key\":\"1993\"}],[\"so\",{\"caption\":\"Chodowieckistraße [Prenzlauer Berg]\",\"key\":\"1994\"}],[\"so\",{\"caption\":\"Chopinstraße [Weißensee]\",\"key\":\"1995\"}],[\"so\",{\"caption\":\"Choriner Straße [Mitte, Prenzlauer Berg]\",\"key\":\"1996\"}],[\"so\",{\"caption\":\"Chorweilerstraße [Altglienicke]\",\"key\":\"1997\"}],[\"so\",{\"caption\":\"Chris-Gueffroy-Allee [Neukölln, Baumschulenweg]\",\"key\":\"1998\"}],[\"so\",{\"caption\":\"Christburger Straße [Prenzlauer Berg]\",\"key\":\"1999\"}],
					[\"so\",{\"caption\":\"Christelweg [Biesdorf]\",\"key\":\"2000\"}]]]]],
	"state":{

Meldungdatum            Details                        Status
15.08.2016 - 19:55 Uhr  Elektroschrott abgelagert      In Bearbeitung
                        Nogatstraße 40, Neukölln       15.08.2016
                                                       19:55 Uhr

		"1225":{
			"childData":{
				"1226":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"1226":{
			"childData":{
				"1227":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"1228":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"1227":{
			"immediate":true,
			"caption":"Elektroschrott abgelagert",
			"styles":[
				"link",
				"ams-betreffButton"],
			"id":"button_betreff"},
		"1228":{
			"text":"Nogatstraße 40, Neukölln",
			"width":"100.0%",
			"id":"label_ortsangabe"},
		"1229":{
			"width":"100.0%"},
		"1230":{
			"childData":{
				"1231":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"1232":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"styles":[
				"ams-anliegenstatus-component"]},
		"1231":{
			"styles":[
				"ams-anliegenstatus-image"],
			"resources":{
				"source":{
					"uRL":"theme://img/ampel-yellow.png"}}},
		"1232":{
			"childData":{
				"1233":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"1234":{
					"alignmentBitmask":5,
					"expandRatio":0}}},
		"1233":{
			"text":"In Bearbeitung",
			"styles":[
				"ams-anliegenstatus-label"]},
		"1234":{
			"contentMode":"PREFORMATTED",
			"text":"15.08.2016\n19:55 Uhr",
			"styles":[
				"ams-preformatted-label",
				"ams-anliegenstatus-aenderungsDatum"],
			"id":"label_status-datum"},

Meldungdatum            Details                           Status
15.08.2016 - 17:43 Uhr  Parkraumbewirtschaftung           Erledigt
                        Margaretenstraße 12, Lichtenberg  15.08.2016
                                                          17:58 Uhr

		"1235":{
			"childData":{
				"1236":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"1236":{
			"childData":{
				"1237":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"1238":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"1237":{
			"immediate":true,
			"caption":"Parkraumbewirtschaftung",
			"styles":[
				"link",
				"ams-betreffButton"],
			"id":"button_betreff"},
		"1238":{
			"text":"Margaretenstraße 12, Lichtenberg",
			"width":"100.0%",
			"id":"label_ortsangabe"},
		"1239":{
			"width":"100.0%"},
		"1240":{
			"childData":{
				"1241":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"1242":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"styles":[
				"ams-anliegenstatus-component"]},
		"1241":{
			"styles":[
				"ams-anliegenstatus-image"],
				"resources":{
					"source":{
						"uRL":"theme://img/ampel-green.png"}}},
		"1242":{
			"childData":{
				"1243":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"1244":{
					"alignmentBitmask":5,
					"expandRatio":0}}},
		"1243":{
			"text":"Erledigt",
			"styles":[
				"ams-anliegenstatus-label"]},
		"1244":{
			"contentMode":"PREFORMATTED",
			"text":"15.08.2016\n17:58 Uhr",
			"styles":[
				"ams-preformatted-label",
				"ams-anliegenstatus-aenderungsDatum"],
			"id":"label_status-datum"},
		"1245":{
			"childData":{
				"1246":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"1246":{
			"childData":{
				"1247":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"1248":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
\"1247\":{\"immediate\":true,\"caption\":\"Park- und Haltverbot nicht berücksichtigt\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1248\":{\"text\":\"Rosenfelder Straße 13, Lichtenberg\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1249\":{\"width\":\"100.0%\"},\"1250\":{\"childData\":{\"1251\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1252\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1251\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1252\":{\"childData\":{\"1253\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1254\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1253\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1254\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n17:48 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1255\":{\"childData\":{\"1256\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1256\":{\"childData\":{\"1257\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1258\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1257\":{\"immediate\":true,\"caption\":\"Verkehrsbehinderung allgemein\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1258\":{\"text\":\"Hohensaatener Straße 1, Marzahn-Hellersdorf\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1259\":{\"width\":\"100.0%\"},\"1260\":{\"childData\":{\"1261\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1262\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1261\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1262\":{\"childData\":{\"1263\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1264\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1263\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1264\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n15:59 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1265\":{\"childData\":{\"1266\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1266\":{\"childData\":{\"1267\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1268\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1267\":{\"immediate\":true,\"caption\":\"Straßenschäden\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1268\":{\"text\":\"Anzengruberstraße 14, Neukölln\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1269\":{\"width\":\"100.0%\"},\"1270\":{\"childData\":{\"1271\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1272\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1271\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1272\":{\"childData\":{\"1273\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1274\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1273\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1274\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n16:51 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1275\":{\"childData\":{\"1276\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1276\":{\"childData\":{\"1277\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1278\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1277\":{\"immediate\":true,\"caption\":\"Park- und Haltverbot nicht berücksichtigt\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1278\":{\"text\":\"Skandinavische Brücke, Lichtenberg\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1279\":{\"width\":\"100.0%\"},\"1280\":{\"childData\":{\"1281\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1282\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1281\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-green.png\"}}},\"1282\":{\"childData\":{\"1283\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1284\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1283\":{\"text\":\"Erledigt\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1284\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n17:04 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1285\":{\"childData\":{\"1286\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1286\":{\"childData\":{\"1287\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1288\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1287\":{\"immediate\":true,\"caption\":\"Bauabfälle abgelagert\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1288\":{\"text\":\"Anzengruberstraße 14, Neukölln\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1289\":{\"width\":\"100.0%\"},\"1290\":{\"childData\":{\"1291\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1292\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1291\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1292\":{\"childData\":{\"1293\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1294\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1293\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1294\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n16:50 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1295\":{\"childData\":{\"1296\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1296\":{\"childData\":{\"1297\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1298\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1297\":{\"immediate\":true,\"caption\":\"Park- und Haltverbot nicht berücksichtigt\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1298\":{\"text\":\"Am Wasserwerk 11, Lichtenberg\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1299\":{\"width\":\"100.0%\"},\"1300\":{\"childData\":{\"1301\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1302\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1301\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-green.png\"}}},\"1302\":{\"childData\":{\"1303\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1304\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1303\":{\"text\":\"Erledigt\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1304\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n16:47 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1305\":{\"childData\":{\"1306\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1306\":{\"childData\":{\"1307\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1308\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1307\":{\"immediate\":true,\"caption\":\"Container\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1308\":{\"text\":\"Röttkenring 12, Lichtenberg\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1309\":{\"width\":\"100.0%\"},\"1310\":{\"childData\":{\"1311\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1312\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1311\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1312\":{\"childData\":{\"1313\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1314\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1313\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1314\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n15:01 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1315\":{\"childData\":{\"1316\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1316\":{\"childData\":{\"1317\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1318\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1317\":{\"immediate\":true,\"caption\":\"Müllablagerung\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1318\":{\"text\":\"Roedernallee 130, Reinickendorf\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1319\":{\"width\":\"100.0%\"},\"1320\":{\"childData\":{\"1321\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1322\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1321\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1322\":{\"childData\":{\"1323\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1324\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1323\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1324\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n14:48 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1325\":{\"childData\":{\"1326\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1326\":{\"childData\":{\"1327\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1328\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1327\":{\"immediate\":true,\"caption\":\"Elektroschrott abgelagert\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1328\":{\"text\":\"Mahlower Straße 22, Neukölln\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1329\":{\"width\":\"100.0%\"},\"1330\":{\"childData\":{\"1331\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1332\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1331\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1332\":{\"childData\":{\"1333\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1334\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1333\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1334\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n16:49 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1335\":{\"childData\":{\"1336\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1336\":{\"childData\":{\"1337\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1338\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1337\":{\"immediate\":true,\"caption\":\"Sperrmüll abgelagert\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1338\":{\"text\":\"Mahlower Straße 22, Neukölln\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1339\":{\"width\":\"100.0%\"},\"1340\":{\"childData\":{\"1341\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1342\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1341\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1342\":{\"childData\":{\"1343\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1344\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1343\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1344\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n15:40 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1345\":{\"childData\":{\"1346\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1346\":{\"childData\":{\"1347\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1348\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1347\":{\"immediate\":true,\"caption\":\"Bauaufsicht\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1348\":{\"text\":\"Oranienburger Chaussee 30, Reinickendorf\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1349\":{\"width\":\"100.0%\"},\"1350\":{\"childData\":{\"1351\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1352\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1351\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1352\":{\"childData\":{\"1353\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1354\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1353\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1354\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n14:22 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1355\":{\"childData\":{\"1356\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1356\":{\"childData\":{\"1357\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1358\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1357\":{\"immediate\":true,\"caption\":\"Elektroschrott abgelagert\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1358\":{\"text\":\"Mahlower Straße 22A, Neukölln\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1359\":{\"width\":\"100.0%\"},\"1360\":{\"childData\":{\"1361\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1362\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1361\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-yellow.png\"}}},\"1362\":{\"childData\":{\"1363\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1364\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1363\":{\"text\":\"In Bearbeitung\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1364\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n15:46 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},\"1365\":{\"childData\":{\"1366\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1366\":{\"childData\":{\"1367\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1368\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"width\":\"100.0%\"},\"1367\":{\"immediate\":true,\"caption\":\"Defekte Ampel\",\"styles\":[\"link\",\"ams-betreffButton\"],\"id\":\"button_betreff\"},\"1368\":{\"text\":\"R-Bhf. Hohenschönhausen, Lichtenberg\",\"width\":\"100.0%\",\"id\":\"label_ortsangabe\"},\"1369\":{\"width\":\"100.0%\"},\"1370\":{\"childData\":{\"1371\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1372\":{\"alignmentBitmask\":5,\"expandRatio\":0}},\"styles\":[\"ams-anliegenstatus-component\"]},\"1371\":{\"styles\":[\"ams-anliegenstatus-image\"],\"resources\":{\"source\":{\"uRL\":\"theme://img/ampel-green.png\"}}},\"1372\":{\"childData\":{\"1373\":{\"alignmentBitmask\":5,\"expandRatio\":0},\"1374\":{\"alignmentBitmask\":5,\"expandRatio\":0}}},\"1373\":{\"text\":\"Erledigt\",\"styles\":[\"ams-anliegenstatus-label\"]},\"1374\":{\"contentMode\":\"PREFORMATTED\",\"text\":\"15.08.2016\\n14:08 Uhr\",\"styles\":[\"ams-preformatted-label\",\"ams-anliegenstatus-aenderungsDatum\"],\"id\":\"label_status-datum\"},
		"847":{
			"loadingIndicatorConfiguration":{
				"firstDelay":2000,
				"secondDelay":5000,
				"thirdDelay":8000},
			"pageState":{
				"title":"Ordnungsamt-Online - Aktuelle Meldungen"},
			"localeServiceState":{
				"localeData":[
					{
						"name":"de_DE",
						"monthNames":[\"Januar\",\"Februar\",\"März\",\"April\",\"Mai\",\"Juni\",\"Juli\",\"August\",\"September\",\"Oktober\",\"November\",\"Dezember\",\"\"],
						"shortMonthNames":[\"Jan\",\"Feb\",\"Mrz\",\"Apr\",\"Mai\",\"Jun\",\"Jul\",\"Aug\",\"Sep\",\"Okt\",\"Nov\",\"Dez\",\"\"],
						"shortDayNames":[\"So\",\"Mo\",\"Di\",\"Mi\",\"Do\",\"Fr\",\"Sa\"],
						"dayNames":[\"Sonntag\",\"Montag\",\"Dienstag\",\"Mittwoch\",\"Donnerstag\",\"Freitag\",\"Samstag\"],
						"firstDayOfWeek":1,
						"dateFormat":"dd.MM.yy",
						"twelveHourClock":false,
						"hourMinuteDelimiter":":",
						"am":null,
						"pm":null}]},
			"theme":"frontend",
			"height":"100.0%",
			"width":"100.0%"},
		"848":{
			"names":[
				"amsTableHeaderKeyDown"]},
		"849":{
			"childLocations":{
				"850":"ams-application-content",
				"851":"ams-jquery-menu",
				"852":"ams-jquery-misc",
				"853":"ams-translation-js",
				"854":"ams-versionNumber",
				"857":"ams-menu",
				"858":"ams-helpLink",
				"862":"ams-datenschutz",
				"866":"ams-nutzungsbedingungen",
				"870":"ams-impressum",
				"874":"ams-sitemap",
				"878":"ams-serviceversprechen",
				"882":"ams-feedbackDialog"},
			"templateContents":"<div class=\"ams-application\"> <!-- start: ams-application -->\n\n    <!--[if lt IE 7]>\n    <div style=\"text-align:left;font-family:Verdana,Arial,sans-serif;font-size:14px;background:#FFFFE1 url(https://www.berlin.de/.img/warning.gif) no-repeat 5px; border-bottom:1px solid;padding:10px 24px;color:black;margin-top:1px;margin-bottom:20px;\">\n        <strong>Warnung!</strong>\n        Sie verwenden eine veraltete, unsichere Version des Microsoft Internet Explorers. Bitte  <a href="http://www.browserchoice.eu/" style="color:blue;text-decoration:underline;"\n                 onclick="window.open(this.href,'_blank','innerHeight=500,innerWidth=850,location=yes');return false;\\\">installieren Sie einen aktuelleren Internet-Browser</a>.\\n    </div>\\n    <div class=\\\"ie6 lang-de\\\">\\n    <![endif]-->\\n    <!--[if IE 7]>\\n    <div class=\\\"ie7 lang-de\\\">\\n    <![endif]-->\\n    <!--[if IE 8]>\\n    <div class=\\\"ie8 lang-de\\\">\\n    <![endif]-->\\n    <!--[if (gt IE 8)|!(IE)]><!-->\\n    <div class=\\\"non-ie lang-de\\\">\\n        <!--<![endif]-->\\n        <div class=\\\"container-wrapper container-portal-header\\\">\\n            <div class=\\\"container\\\">\\n                <div class=\\\"row\\\" role=\\\"banner\\\">\\n                    <div class=\\\"span12\\\">\\n                        <!-- template start: header_portal -->\\n                        <div class=\\\"html5-header portal-header\\\">\\n                            <div class=\\\"red-line\\\"></div>\\n                            <div class=\\\"html5-figure main-image\\\">\\n                                <a href=\\\"http://www.berlin.de/\\\" title=\\\"Link führt zur Startseite von Berlin.de\\\">\\n                                    <img class=\\\"portal-logo hide-mobile\\\" src=\\\"https://www.berlin.de/_bde/css/berlin_de.png\\\"\\n                                         alt=\\\"Bild zeigt: Berlin.de Logo\\\" title=\\\"Link führt zur Startseite von Berlin.de\\\"/>\\n                                </a>\\n                            </div>\\n                            <div class=\\\"html5-nav\\\" aria-label=\\\"Globale Portal-Links\\\" role=\\\"navigation\\\">\\n                                <p id=\\\"bo-portalnavilinkslabel\\\" class=\\\"aural\\\">Besuchen Sie auch unsere anderen Themen-Bereiche:</p>\\n                                <ul class=\\\"portal-navi\\\" id=\\\"bo-portalnavilinks\\\" aria-labelledby=\\\"bo-portalnavilinkslabel\\\">\\n                                    <li class=\\\"active\\\"><a href=\\\"http://www.berlin.de/rubrik/politik-und-verwaltung/\\\">Politik, Verwaltung, Bürger</a></li>\\n                                    <li><a href=\\\"http://www.berlin.de/kultur-und-tickets/\\\">Kultur & Ausgehen</a></li>\\n                                    <li><a href=\\\"http://www.berlin.de/tourismus/\\\">Tourismus</a></li>\\n                                    <li><a href=\\\"http://www.berlin.de/wirtschaft/\\\">Wirtschaft</a></li>\\n                                    <li><a href=\\\"http://www.berlin.de/special/\\\">Themen</a></li>\\n                                    <li><a href=\\\"http://www.berlin.de/adressen/\\\">BerlinFinder</a></li>\\n                                    <li><a href=\\\"http://www.berlin.de/stadtplan/\\\">Stadtplan</a></li>\\n                                </ul>\\n                            </div>\\n                        </div>\\n                        <!-- /template end: header_portal -->\\n                    </div>\\n                </div>\\n            </div>\\n        </div>\\n\\n        <div class=\\\"container-wrapper container-content\\\">\\n            <div class=\\\"container\\\">\\n                <div class=\\\"row\\\">\\n                    <div class=\\\"span12\\\">\\n\\n                        <!-- template start: header_content -->\\n\\n                        <div class=\\\"row html5-header content-header buergertelefon-dependent\\\" role=\\\"banner\\\">\\n                            <div class=\\\"span5\\\">\\n                                <div class=\\\"html5-section section-logo without-logo\\\">\\n                                    <div class=\\\"html5-section text\\\">\\n                                        <a href=\\\"#\\\" title=\\\"Link führt zur Startseite von Ordnungsamt-Online\\\">\\n                                            <span class=\\\"institution\\\">Ordnungsamt-Online</span>\\n                                            <span class=\\\"title\\\">Die Berliner Ordnungsämter</span>\\n                                        </a>\\n                                    </div>\\n                                </div>\\n                            </div>\\n                            <div class=\\\"span7\\\">\\n                                <div class=\\\"html5-nav meta-navi\\\">\\n                                    <ul class=\\\"nav\\\">\\n                                        <li title=\\\"Service-Versprechen\\\">\\n                                            <div location=\\\"ams-nutzungsbedingungen\\\"></div>\\n                                        </li>\\n                                        <li title=\\\"Service-Versprechen\\\">\\n                                            <div location=\\\"ams-serviceversprechen\\\"></div>\\n                                        </li>\\n                                        <li title=\\\"Hilfe\\\">\\n                                            <span location=\\\"ams-helpLink\\\"></span>\\n                                        </li>\\n                                        <li>\\n                                            <div location=\\\"ams-restartApplication\\\"></div>\\n                                        </li>\\n                                    </ul>\\n                                </div>\\n                            </div>\\n                        </div>\\n\\n                        <!-- /template end: header_content -->\\n\\n                        <div class=\\\"row\\\" role=\\\"navigation\\\">\\n                            <div class=\\\"span12\\\">\\n                                <!-- template start: navi-top -->\\n                                <div class=\\\"content-navi-wrapper navbar buergertelefon-dependent\\\">\\n                                    <div class=\\\"html5-nav content-navi-top navbar-inner\\\">\\n\\n                                        <div class=\\\"nav-collapse mainnav-collapse collapse\\\" location=\\\"ams-menu\\\"></div>\\n                                        <div class=\\\"beberlin\\\"><span class=\\\"bb-logo\\\">Startseite</span></div>\\n                                    </div>\\n                                </div>\\n                                <!-- /template end: navi-top -->\\n                            </div>\\n                        </div>\\n\\n\\n                        <div class=\\\"row\\\" role=\\\"main\\\">\\n\\n                            <div class=\\\"span12 column-content\\\">\\n                                <div class=\\\"html5-section article\\\">\\n                                    <!-- template start: ams-application-contnet -->\\n                                    <div class=\\\"html5-section body\\\">\\n                                        <div location=\\\"ams-application-content\\\"></div>\\n                                    </div>\\n                                    <!-- template end: ams-application-contnet -->\\n                                </div>\\n                            </div>\\n\\n                        </div>\\n\\n\\n                        <div class=\\\"row\\\" role=\\\"contentinfo\\\">\\n                            <div class=\\\"span12\\\">\\n                                <!-- template start: footer-content -->\\n                                <div class=\\\"html5-footer content-footer\\\">\\n                                    <div class=\\\"html5-nav\\\">\\n                                        <ul class=\\\"nav\\\">\\n                                            <li class=\\\"icon-footer icon-imprint_32 icon-separator\\\" title=\\\"Impressum\\\">\\n                                                <span location=\\\"ams-impressum\\\"></span>\\n                                            </li>\\n                                            <li class=\\\"icon-footer icon-information_32 icon-separator\\\" title=\\\"Datenschutz\\\">\\n                                                <span location=\\\"ams-datenschutz\\\"></span>\\n                                            </li>\\n                                            <li title=\\\"Feedback\\\">\\n                                                <span location=\\\"ams-feedbackDialog\\\"></span>\\n                                            </li>\\n                                            <li title=\\\"Sitemap\\\">\\n                                                <span location=\\\"ams-sitemap\\\"></span>\\n                                            </li>\\n                                            <li style=\\\"padding-top: 7px; width: 38%\\\">\\n                                                <span class=\\\"ams-versionnumber align-right-footer\\\" location=\\\"ams-versionNumber\\\"></span>\\n                                            </li>\\n                                        </ul>\\n\\n                                    </div>\\n                                </div>\\n                                <!-- /template end: footer-content -->\\n                            </div>\\n                        </div>\\n\\n                    </div>\\n                    <!-- /row html5-section content type-article -->\\n                </div>\\n            </div>\\n        </div>\\n\\n        <div location=\\\"ams-jquery-menu\\\"></div>\\n        <div location=\\\"ams-jquery-misc\\\"></div>\\n        <div location=\\\"ams-translation-js\\\"></div>\\n\\n\\n    </div>\\n    <!-- end: ams-application -->\\n\",
			"width":"100.0%"},
		"850":{
			"height":"100.0%",
			"width":"100.0%"},
		"851":{
			"callbackNames":[],
			"rpcInterfaces":[],
			"height":"",
			"width":"",
			"readOnly":false,
			"immediate":false,
			"description":"",
			"captionAsHtml":false,
			"resources":[],
			"enabled":true},
		"852":{
			"callbackNames":[],
			"rpcInterfaces":[],
			"height":"",
			"width":"",
			"readOnly":false,
			"immediate":false,
			"description":"",
			"captionAsHtml":false,
			"resources":[],
			"enabled":true},
		"853":{
			"callbackNames":[],
			"rpcInterfaces":[],
			"height":"",
			"width":"",
			"readOnly":false,
			"immediate":false,
			"description":"",
			"captionAsHtml":false,
			"resources":[],
			"enabled":true},
		"854":{
			"height":"100.0%",
			"width":"100.0%"},
		"855":{
			"childData":{
				"856":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"height":"100.0%",
			"width":"100.0%"},
		"856":{
			"immediate":true,
			"caption":"",
			"styles":[
				"link"],
			"id":"button_versionNumber"},
		"857":{
			"templateContents":"<ul class=\\\"nav level1\\\">\\n<li class=\\\"active\\\">\\n<a id=\\\"meldungAktuell\\\" href=\\\"#!meldungAktuell\\\">Aktuelle Meldungen</a>\\n</li>\\n<li>\\n<a id=\\\"meldungNeu\\\" href=\\\"#!meldungNeu\\\">Neue Meldung erfassen</a>\\n</li>\\n<li class=\\\"ams-menu-nobackground\\\">\\n<a id=\\\"mobileApp\\\" target=\\\"_blank\\\" href=\\\"http://www.berlin.de/ordnungsamt-online/mobile-app/\\\">Mobile App</a>\\n</li>\\n</ul>\\n\",
			"width":"100.0%",
			"styles":[
				"ams-menuLayout"],
			"primaryStyleName":"ams-menuLayout"},
		"858":{
			"width":"100.0%"},
		"859":{
			"childData":{
				"860":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%",
			"styles":[
				"ams-help-link-component"]},
		"860":{
			"immediate":true,
			"caption":"Hilfe",
			"styles":[
				"link",
				"ams-startpage-link",
				"ams-help-link"],
			"id":"button_help"},
		"861":{
			"target":"ams-help",
			"features":"height=500,width=700",
			"uriFragment":"!anwendungsHilfe?page=meldungAktuell",
			"parameters":{
				"redirect-mobile":"ignore"},
			"resources":{
				"url":{
					"uRL":"app://hilfe/"}}},
		"862":{
			"width":"100.0%"},
		"863":{
			"childData":{
				"864":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"864":{
			"immediate":true,
			"caption":"Datenschutz",
			"styles":[
				"link"],
			"id":"button_datenschutz"},
		"865":{
			"target":"ams-datenschutz",
			"features":"height=768,width=1024",
			"uriFragment":"!datenschutz",
			"parameters":{
				"redirect-mobile":"ignore"},
			"resources":{
				"url":{
					"uRL":"app://"}}},
		"866":{
			"width":"100.0%"},
		"867":{
			"childData":{
				"868":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"868":{
			"immediate":true,
			"caption":"Nutzungsbedingungen",
			"styles":[
				"link",
				"ams-startpage-link"],
			"id":"button_nutzungsbed"},
		"869":{
			"target":"ams-nutzungsbedingungen",
			"features":"height=768,width=1024",
			"uriFragment":"!nutzungsbedingungen",
			"parameters":{
				"redirect-mobile":"ignore"},
			"resources":{
				"url":{
					"uRL":"app://"}}},
		"870":{
			"width":"100.0%"},
		"871":{
			"childData":{
				"872":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"872":{
			"immediate":true,
			"caption":"Impressum",
			"styles":[
				"link"],
			"id":"button_impressum"},
		"873":{
			"target":"ams-impressum",
			"features":"height=768,width=1024",
			"uriFragment":"!impressum",
			"parameters":{
				"redirect-mobile":"ignore"},
			"resources":{
				"url":{
					"uRL":"app://"}}},
		"874":{
			"width":"100.0%"},
		"875":{
			"childData":{
				"876":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"876":{
			"immediate":true,
			"caption":"Sitemap",
			"styles":[
				"link"],
			"id":"button_sitemap"},
		"877":{
			"target":"ams-sitemap",
			"features":"height=768,width=1024",
			"uriFragment":"!sitemap",
			"parameters":{
				"redirect-mobile":"ignore"},
			"resources":{
				"url":{
					"uRL":"app://"}}},
		"878":{
			"width":"100.0%"},
		"879":{
			"childData":{
				"880":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"880":{
			"immediate":true,
			"caption":"Serviceversprechen",
			"styles":[
				"link",
				"ams-startpage-link"],
			"id":"button_servicever"},
		"881":{
			"target":"ams-serviceversprechen",
			"features":"height=768,width=1024",
			"uriFragment":"!serviceversprechen",
			"parameters":{
				"redirect-mobile":"ignore"},
			"resources":{
				"url":{
					"uRL":"app://"}}},
		"882":{
			"width":"100.0%"},
		"883":{
			"immediate":true,
			"caption":"Feedback",
			"styles":[
				"link"],
			"id":"button_feedback"},
		"884":{
			"height":"100.0%",
			"width":"100.0%"},
		"885":{
			"childData":{
				"886":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"887":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"920":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"height":"100.0%",
			"width":"100.0%",
			"styles":[
				"ams-aktuelleMeldungen-view"]},
		"886":{
			"contentMode":"HTML",
			"text":"<h1>Aktuelle Meldungen</h1>",
			"width":"100.0%",
			"styles":[
				"html5-header"],
			"id":"label_header"},
		"887":{
			"height":"100.0%",
			"width":"100.0%"},
		"888":{
			"childData":{
				"889":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"890":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"891":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"917":{
					"alignmentBitmask":34,
					"expandRatio":0}},
			"height":"100.0%",
			"width":"100.0%",
			"styles":[
				"ams-meldungSuche-view"]},
		"889":{
			"contentMode":"HTML",
			"text":"<h2>Meldungen durchsuchen</h2>",
			"width":"100.0%",
			"styles":[
				"ams-meldungSuche-header"],
			"id":"label_meldungSucheHeader"},
		"890":{
			"text":"Hinweis: Ihre Meldung ist nicht sofort nach dem Absenden sichtbar.",
			"width":"100.0%",
			"styles":[
				"ams-meldungSuche-meldungNummerhinweisText"],
			"id":"label_hinweisText"},
		"891":{
			"childData":{
				"892":{
					"alignmentBitmask":5,
					"expandRatio":0.5},
				"902":{
					"alignmentBitmask":5,
					"expandRatio":0.5}},
			"height":"100.0%",
			"width":"100.0%"},
		"892":{
			"spacing":true,
			"childData":{
				"893":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"895":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"897":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"899":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"marginsBitmask":5,
			"width":"100.0%"},
		"893":{
			"maxLength":6,
			"text":"",
			"immediate":true,
			"caption":"Meldungsnummer",
			"styles":[
				"ams-meldungSuche-meldungNummer"],
			"id":"textField_meldungsNummer"},
		"895":{
			"maxLength":1000,
			"text":"",
			"immediate":true,
			"caption":"Suchbegriff",
			"id":"textField_suchBegriff"},
		"897":{
			"immediate":true,
			"caption":"Bezirk",
			"id":"comboBox_bezirk"},
		"899":{
			"descriptionForAssistiveDevices":"Die Pfeil-nach-unten-Taste öffnet ein Kalenderelement zur Datumsauswahl.",
			"immediate":true,
			"caption":"Zeitraum von",
			"id":"dateField_zeitRaumVon"},
		"900":{
			"title":"Datumsauswahldialog öffnen"},
		"902":{
			"spacing":true,
			"childData":{
				"903":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"904":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"912":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"914":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"marginsBitmask":5,
			"width":"100.0%",
			"styles":[
				"ams-meldungSuche-rightSearchForm"]},
		"903":{
			"width":"100.0%"},
		"904":{
			"width":"100.0%",
			"immediate":true,
			"caption":"Status",
			"id":"statusCustomField"},
		"905":{
			"spacing":true,
			"childData":{
				"908":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"910":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"styles":[
				"ams-status-customField"]},
		"908":{
			"immediate":true,
			"caption":"In Bearbeitung",
			"styles":[
				"ams-checkbox-unchecked"],
			"id":"checkBox_statusInBearbeitung"},
		"910":{
			"immediate":true,
			"caption":"Erledigt",
			"styles":[
				"ams-checkbox-unchecked"],
			"id":"checkBox_statusErledigt"},
		"912":{
			"immediate":true,
			"caption":"Straße",
			"id":"comboBox_strasse"},
		"914":{
			"descriptionForAssistiveDevices":"Die Pfeil-nach-unten-Taste öffnet ein Kalenderelement zur Datumsauswahl.",
			"immediate":true,
			"caption":"Zeitraum bis",
			"id":"dateField_zeitRaumBis"},
		"915":{
			"title":"Datumsauswahldialog öffnen"},
		"917":{
			"childData":{
				"918":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"919":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"styles":[
				"ams-buttonPanel"]},
		"918":{
			"immediate":true,
			"caption":"Zurücksetzen",
			"id":"button_zuruecksetzen"},
		"919":{
			"immediate":true,
			"caption":"Suchen",
			"id":"button_suchen"},
		"920":{
			"height":"100.0%",
			"width":"100.0%",
			"immediate":true,
			"styles":[
				"ams-table"]},
		"921":{
			"childData":{
				"922":{
					"alignmentBitmask":5,
					"expandRatio":0},
				"923":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"width":"100.0%"},
		"922":{
			"width":"100.0%",
			"immediate":true,
			"caption":"",
			"styles":[
				"ams-table"],
			"id":"table_ergebnisse",
			"registeredEventListeners":[
				"columnReorder",
				"clientConnectorAttach"]},
		"923":{
			"spacing":true,
			"childData":{
				"924":{
					"alignmentBitmask":5,
					"expandRatio":0}},
			"styles":[
				"ams-table-footer"]},
		"924":{
			"caption":"3.968 Ergebnisse gefunden",
			"id":"label_rowCount"}},
	"types":{
		"1225":"4",
		"1226":"10",
		"1227":"14",
		"1228":"12",
		"1229":"5",
		"1230":"4",
		"1231":"9",\"1232\":\"10\",\"1233\":\"12\",\"1234\":\"12\",\"1235\":\"4\",\"1236\":\"10\",\"1237\":\"14\",\"1238\":\"12\",\"1239\":\"5\",\"1240\":\"4\",\"1241\":\"9\",\"1242\":\"10\",\"1243\":\"12\",\"1244\":\"12\",\"1245\":\"4\",\"1246\":\"10\",\"1247\":\"14\",\"1248\":\"12\",\"1249\":\"5\",\"1250\":\"4\",\"1251\":\"9\",\"1252\":\"10\",\"1253\":\"12\",\"1254\":\"12\",\"1255\":\"4\",\"1256\":\"10\",\"1257\":\"14\",\"1258\":\"12\",\"1259\":\"5\",\"1260\":\"4\",\"1261\":\"9\",\"1262\":\"10\",\"1263\":\"12\",\"1264\":\"12\",\"1265\":\"4\",\"1266\":\"10\",\"1267\":\"14\",\"1268\":\"12\",\"1269\":\"5\",\"1270\":\"4\",\"1271\":\"9\",\"1272\":\"10\",\"1273\":\"12\",\"1274\":\"12\",\"1275\":\"4\",\"1276\":\"10\",\"1277\":\"14\",\"1278\":\"12\",\"1279\":\"5\",\"1280\":\"4\",\"1281\":\"9\",\"1282\":\"10\",\"1283\":\"12\",\"1284\":\"12\",\"1285\":\"4\",\"1286\":\"10\",\"1287\":\"14\",\"1288\":\"12\",\"1289\":\"5\",\"1290\":\"4\",\"1291\":\"9\",\"1292\":\"10\",\"1293\":\"12\",\"1294\":\"12\",\"1295\":\"4\",\"1296\":\"10\",\"1297\":\"14\",\"1298\":\"12\",\"1299\":\"5\",\"1300\":\"4\",\"1301\":\"9\",\"1302\":\"10\",\"1303\":\"12\",\"1304\":\"12\",\"1305\":\"4\",\"1306\":\"10\",\"1307\":\"14\",\"1308\":\"12\",\"1309\":\"5\",\"1310\":\"4\",\"1311\":\"9\",\"1312\":\"10\",\"1313\":\"12\",\"1314\":\"12\",\"1315\":\"4\",\"1316\":\"10\",\"1317\":\"14\",\"1318\":\"12\",\"1319\":\"5\",\"1320\":\"4\",\"1321\":\"9\",\"1322\":\"10\",\"1323\":\"12\",\"1324\":\"12\",\"1325\":\"4\",\"1326\":\"10\",\"1327\":\"14\",\"1328\":\"12\",\"1329\":\"5\",\"1330\":\"4\",\"1331\":\"9\",\"1332\":\"10\",\"1333\":\"12\",\"1334\":\"12\",\"1335\":\"4\",\"1336\":\"10\",\"1337\":\"14\",\"1338\":\"12\",\"1339\":\"5\",\"1340\":\"4\",\"1341\":\"9\",\"1342\":\"10\",\"1343\":\"12\",\"1344\":\"12\",\"1345\":\"4\",\"1346\":\"10\",\"1347\":\"14\",\"1348\":\"12\",\"1349\":\"5\",\"1350\":\"4\",\"1351\":\"9\",\"1352\":\"10\",\"1353\":\"12\",\"1354\":\"12\",\"1355\":\"4\",\"1356\":\"10\",\"1357\":\"14\",\"1358\":\"12\",\"1359\":\"5\",\"1360\":\"4\",\"1361\":\"9\",\"1362\":\"10\",\"1363\":\"12\",\"1364\":\"12\",\"1365\":\"4\",\"1366\":\"10\",\"1367\":\"14\",\"1368\":\"12\",\"1369\":\"5\",\"1370\":\"4\",\"1371\":\"9\",\"1372\":\"10\",\"1373\":\"12\",\"1374\":\"12\",\"847\":\"0\",\"848\":\"25\",\"849\":\"1\",\"850\":\"13\",\"851\":\"23\",\"852\":\"27\",\"853\":\"26\",\"854\":\"31\",\"855\":\"10\",\"856\":\"14\",\"857\":\"2\",\"858\":\"22\",\"859\":\"10\",\"860\":\"14\",\"861\":\"16\",\"862\":\"34\",\"863\":\"10\",\"864\":\"14\",\"865\":\"16\",\"866\":\"20\",\"867\":\"10\",\"868\":\"14\",\"869\":\"16\",\"870\":\"18\",\"871\":\"10\",\"872\":\"14\",\"873\":\"16\",\"874\":\"28\",\"875\":\"10\",\"876\":\"14\",\"877\":\"16\",\"878\":\"17\",\"879\":\"10\",\"880\":\"14\",\"881\":\"16\",\"882\":\"21\",\"883\":\"14\",\"884\":\"32\",\"885\":\"10\",\"886\":\"12\",\"887\":\"33\",\"888\":\"10\",\"889\":\"12\",\"890\":\"12\",\"891\":\"4\",\"892\":\"11\",\"893\":\"6\",\"894\":\"15\",\"895\":\"6\",\"896\":\"15\",\"897\":\"8\",\"898\":\"15\",\"899\":\"7\",\"900\":\"30\",\"901\":\"15\",\"902\":\"11\",\"903\":\"12\",\"904\":\"24\",\"905\":\"4\",\"908\":\"29\",\"909\":\"15\",\"910\":\"29\",\"911\":\"15\",\"912\":\"8\",\"913\":\"15\",\"914\":\"7\",\"915\":\"30\",\"916\":\"15\",\"917\":\"4\",\"918\":\"14\",\"919\":\"14\",\"920\":\"19\",\"921\":\"10\",\"922\":\"3\",\"923\":\"4\",\"924\":\"12\"},
	"hierarchy":{
		"1225":[
			"1226"],
		"1226":[
			"1227",
			"1228"],
		"1227":[],
		"1228":[],\"1229\":[\"1230\"],\"1230\":[\"1231\",\"1232\"],\"1231\":[],\"1232\":[\"1233\",\"1234\"],\"1233\":[],\"1234\":[],\"1235\":[\"1236\"],\"1236\":[\"1237\",\"1238\"],\"1237\":[],\"1238\":[],\"1239\":[\"1240\"],\"1240\":[\"1241\",\"1242\"],\"1241\":[],\"1242\":[\"1243\",\"1244\"],\"1243\":[],\"1244\":[],\"1245\":[\"1246\"],\"1246\":[\"1247\",\"1248\"],\"1247\":[],\"1248\":[],\"1249\":[\"1250\"],\"1250\":[\"1251\",\"1252\"],\"1251\":[],\"1252\":[\"1253\",\"1254\"],\"1253\":[],\"1254\":[],\"1255\":[\"1256\"],\"1256\":[\"1257\",\"1258\"],\"1257\":[],\"1258\":[],\"1259\":[\"1260\"],\"1260\":[\"1261\",\"1262\"],\"1261\":[],\"1262\":[\"1263\",\"1264\"],\"1263\":[],\"1264\":[],\"1265\":[\"1266\"],\"1266\":[\"1267\",\"1268\"],\"1267\":[],\"1268\":[],\"1269\":[\"1270\"],\"1270\":[\"1271\",\"1272\"],\"1271\":[],\"1272\":[\"1273\",\"1274\"],\"1273\":[],\"1274\":[],\"1275\":[\"1276\"],\"1276\":[\"1277\",\"1278\"],\"1277\":[],\"1278\":[],\"1279\":[\"1280\"],\"1280\":[\"1281\",\"1282\"],\"1281\":[],\"1282\":[\"1283\",\"1284\"],\"1283\":[],\"1284\":[],\"1285\":[\"1286\"],\"1286\":[\"1287\",\"1288\"],\"1287\":[],\"1288\":[],\"1289\":[\"1290\"],\"1290\":[\"1291\",\"1292\"],\"1291\":[],\"1292\":[\"1293\",\"1294\"],\"1293\":[],\"1294\":[],\"1295\":[\"1296\"],\"1296\":[\"1297\",\"1298\"],\"1297\":[],\"1298\":[],\"1299\":[\"1300\"],\"1300\":[\"1301\",\"1302\"],\"1301\":[],\"1302\":[\"1303\",\"1304\"],\"1303\":[],\"1304\":[],\"1305\":[\"1306\"],\"1306\":[\"1307\",\"1308\"],\"1307\":[],\"1308\":[],\"1309\":[\"1310\"],\"1310\":[\"1311\",\"1312\"],\"1311\":[],\"1312\":[\"1313\",\"1314\"],\"1313\":[],\"1314\":[],\"1315\":[\"1316\"],\"1316\":[\"1317\",\"1318\"],\"1317\":[],\"1318\":[],\"1319\":[\"1320\"],\"1320\":[\"1321\",\"1322\"],\"1321\":[],\"1322\":[\"1323\",\"1324\"],\"1323\":[],\"1324\":[],\"1325\":[\"1326\"],\"1326\":[\"1327\",\"1328\"],\"1327\":[],\"1328\":[],\"1329\":[\"1330\"],\"1330\":[\"1331\",\"1332\"],\"1331\":[],\"1332\":[\"1333\",\"1334\"],\"1333\":[],\"1334\":[],\"1335\":[\"1336\"],\"1336\":[\"1337\",\"1338\"],\"1337\":[],\"1338\":[],\"1339\":[\"1340\"],\"1340\":[\"1341\",\"1342\"],\"1341\":[],\"1342\":[\"1343\",\"1344\"],\"1343\":[],\"1344\":[],\"1345\":[\"1346\"],\"1346\":[\"1347\",\"1348\"],\"1347\":[],\"1348\":[],\"1349\":[\"1350\"],\"1350\":[\"1351\",\"1352\"],\"1351\":[],\"1352\":[\"1353\",\"1354\"],\"1353\":[],\"1354\":[],\"1355\":[\"1356\"],\"1356\":[\"1357\",\"1358\"],\"1357\":[],\"1358\":[],\"1359\":[\"1360\"],\"1360\":[\"1361\",\"1362\"],\"1361\":[],\"1362\":[\"1363\",\"1364\"],\"1363\":[],\"1364\":[],\"1365\":[\"1366\"],\"1366\":[\"1367\",\"1368\"],\"1367\":[],\"1368\":[],\"1369\":[\"1370\"],\"1370\":[\"1371\",\"1372\"],\"1371\":[],\"1372\":[\"1373\",\"1374\"],\"1373\":[],\"1374\":[],\"847\":[\"849\",\"848\"],\"848\":[],\"849\":[\"853\",\"857\",\"854\",\"874\",\"878\",\"866\",\"858\",\"852\",\"882\",\"851\",\"850\",\"870\",\"862\"],\"850\":[\"884\"],\"851\":[],\"852\":[],\"853\":[],\"854\":[\"855\"],\"855\":[\"856\"],\"856\":[],\"857\":[],\"858\":[\"859\"],\"859\":[\"860\"],\"860\":[\"861\"],\"861\":[],\"862\":[\"863\"],\"863\":[\"864\"],\"864\":[\"865\"],\"865\":[],\"866\":[\"867\"],\"867\":[\"868\"],\"868\":[\"869\"],\"869\":[],\"870\":[\"871\"],\"871\":[\"872\"],\"872\":[\"873\"],\"873\":[],\"874\":[\"875\"],\"875\":[\"876\"],\"876\":[\"877\"],\"877\":[],\"878\":[\"879\"],\"879\":[\"880\"],\"880\":[\"881\"],\"881\":[],\"882\":[\"883\"],\"883\":[],\"884\":[\"885\"],\"885\":[\"886\",\"887\",\"920\"],\"886\":[],\"887\":[\"888\"],\"888\":[\"889\",\"890\",\"891\",\"917\"],\"889\":[],\"890\":[],\"891\":[\"892\",\"902\"],\"892\":[\"893\",\"895\",\"897\",\"899\"],\"893\":[\"894\"],\"894\":[],\"895\":[\"896\"],\"896\":[],\"897\":[\"898\"],\"898\":[],\"899\":[\"900\",\"901\"],\"900\":[],\"901\":[],\"902\":[\"903\",\"904\",\"912\",\"914\"],\"903\":[],\"904\":[\"905\"],\"905\":[\"908\",\"910\"],\"908\":[\"909\"],\"909\":[],\"910\":[\"911\"],\"911\":[],\"912\":[\"913\"],\"913\":[],\"914\":[\"915\",\"916\"],\"915\":[],\"916\":[],\"917\":[\"918\",\"919\"],\"918\":[],\"919\":[],\"920\":[\"921\"],\"921\":[\"922\",\"923\"],\"922\":[\"1329\",\"1369\",\"1335\",\"1269\",\"1249\",\"1319\",\"1295\",\"1309\",\"1359\",\"1299\",\"1235\",\"1285\",\"1339\",\"1365\",\"1229\",\"1265\",\"1325\",\"1345\",\"1225\",\"1305\",\"1275\",\"1355\",\"1255\",\"1315\",\"1279\",\"1349\",\"1239\",\"1259\",\"1289\",\"1245\"],\"923\":[\"924\"],\"924\":[]},
	"rpc" : [
		[
			"848",
			"com.vaadin.shared.extension.javascriptmanager.ExecuteJavaScriptRpc",
			"executeJavaScript",
			[
				"$('#optionGroup_mapSource input[checked]').focus()"]],
		[
			"848",
			"com.vaadin.shared.extension.javascriptmanager.ExecuteJavaScriptRpc",
			"executeJavaScript",
			[
				"window.at_techtalk_ams_commons_web_js_Menu();"]],
		[
			"848",
			"com.vaadin.shared.extension.javascriptmanager.ExecuteJavaScriptRpc",
			"executeJavaScript",
			[
				"window.at_techtalk_ams_commons_web_js_Misc();"]],
		[
			"848",
			"com.vaadin.shared.extension.javascriptmanager.ExecuteJavaScriptRpc",
			"executeJavaScript",
			[
				"window.at_techtalk_ams_commons_web_js_Translation();"]]],
	"meta" : {
		"repaintAll":true},
	"resources" : {
		},
	"typeMappings" : {
		"com.vaadin.ui.TextField" : 35 ,
		"com.vaadin.ui.AbstractJavaScriptComponent" : 36 ,
		"at.techtalk.ams.frontend.web.servicelinks.serviceversprechen.ServiceversprechenComponent" : 17 ,
		"com.vaadin.ui.AbstractTextField" : 37 ,
		"com.vaadin.ui.CheckBox" : 29 ,
		"at.techtalk.ams.commons.web.js.Misc" : 27 ,
		"com.vaadin.ui.UI" : 38 ,
		"at.techtalk.ams.commons.web.ContentComponent" : 39 ,
		"com.vaadin.server.BrowserWindowOpener" : 16 ,
		"com.vaadin.ui.JavaScript" : 25 ,
		"com.vaadin.ui.AbstractSingleComponentContainer" : 40 ,
		"com.vaadin.ui.CustomField" : 41 ,
		"at.techtalk.ams.frontend.web.servicelinks.datenschutz.DatenschutzComponent" : 34 ,
		"at.techtalk.ams.commons.web.MasterLayout" : 42 ,
		"com.vaadin.ui.AbstractField" : 43 ,
		"com.vaadin.ui.AbstractOrderedLayout" : 44 ,
		"at.techtalk.ams.commons.web.addons.DateFieldTitleExtension" : 30 ,
		"com.vaadin.ui.DateField" : 45 ,
		"at.techtalk.ams.frontend.web.servicelinks.sitemap.FrontendSiteMapComponent" : 28 ,
		"at.techtalk.ams.commons.web.controls.tables.AmsTable" : 19 ,
		"com.vaadin.ui.AbstractSelect" : 46 ,
		"at.techtalk.ams.commons.web.AmsUI" : 47 ,
		"com.vaadin.ui.Button" : 14 ,
		"com.vaadin.ui.AbstractComponent" : 48 ,
		"at.techtalk.ams.frontend.web.servicelinks.nutzungsbedingungen.NutzungsBedingungenComponent" : 20 ,
		"at.techtalk.ams.frontend.web.servicelinks.impressum.ImpressumComponent" : 18 ,
		"at.techtalk.ams.commons.web.controls.AmsPopupDateField" : 7 ,
		"com.vaadin.server.AbstractClientConnector" : 49 ,
		"com.vaadin.ui.Image" : 9 ,
		"com.vaadin.server.AbstractExtension" : 50 ,
		"at.techtalk.ams.frontend.web.FrontendMasterLayout" : 1 ,
		"com.vaadin.ui.HorizontalLayout" : 4 ,
		"at.techtalk.ams.commons.web.components.AnliegenStatusComponent" : 5 ,
		"at.techtalk.ams.commons.web.js.Translation" : 26 ,
		"com.vaadin.ui.FormLayout" : 11 ,
		"at.techtalk.ams.commons.web.js.Menu" : 23 ,
		"at.techtalk.ams.frontend.web.feedback.FeedbackComponent" : 21 ,
		"com.vaadin.ui.PopupDateField" : 51 ,
		"com.vaadin.ui.AbstractComponentContainer" : 52 ,
		\"com.vaadin.ui.AbstractEmbedded\" : 53 , \"at.techtalk.ams.commons.web.addons.SpanToLabelExtension\" : 15 , \"com.vaadin.ui.Table\" : 3 , \"at.techtalk.ams.commons.web.components.StatusCustomField\" : 24 , \"at.techtalk.ams.frontend.web.meldung.aktuell.MeldungSucheView\" : 33 , \"com.vaadin.ui.ComboBox\" : 54 , \"at.techtalk.ams.frontend.web.help.FrontendHelpComponent\" : 22 , \"com.vaadin.ui.CustomLayout\" : 55 , \"com.vaadin.ui.VerticalLayout\" : 10 , \"at.techtalk.ams.commons.web.components.VersionNumberComponent\" : 31 , \"at.techtalk.ams.frontend.web.FrontendUI\" : 0 , \"at.techtalk.ams.frontend.web.meldung.aktuell.MeldungAktuellView\" : 32 , \"at.techtalk.ams.commons.web.navigation.VaadinView\" : 13 , \"com.vaadin.ui.CustomComponent\" : 56 , \"at.techtalk.ams.commons.web.controls.CharacterFilteringTextField\" : 6 , \"com.vaadin.ui.Label\" : 12 , \"at.techtalk.ams.commons.web.menu.MenuLayout\" : 2 , \"at.techtalk.vaadin.asyncfiltercombobox.AsyncFilterComboBox\" : 57 , \"com.vaadin.ui.AbstractLayout\" : 58 , \"at.techtalk.ams.commons.web.components.AmsComboBox\" : 8 , \"at.techtalk.ams.commons.web.View\" : 59 },
	"typeInheritanceMap" : {
		"35" : 37 ,
		"36" : 48 ,
		"17" : 56 ,
		\"37\" : 43 , \"29\" : 43 , \"27\" : 36 , \"38\" : 40 , \"39\" : 56 , \"16\" : 50 , \"25\" : 50 , \"40\" : 48 , \"41\" : 43 , \"34\" : 56 , \"42\" : 55 , \"43\" : 48 , \"44\" : 58 , \"30\" : 50 , \"45\" : 43 , \"28\" : 56 , \"19\" : 59 , \"46\" : 43 , \"47\" : 38 , \"14\" : 48 , \"48\" : 49 , \"20\" : 56 , \"18\" : 56 , \"7\" : 51 , \"9\" : 53 , \"50\" : 49 , \"1\" : 42 , \"4\" : 44 , \"5\" : 56 , \"26\" : 36 , \"11\" : 44 , \"23\" : 36 , \"21\" : 56 , \"51\" : 45 , \"52\" : 48 , \"53\" : 48 , \"15\" : 50 , \"3\" : 46 , \"24\" : 41 , \"33\" : 59 , \"54\" : 46 , \"22\" : 56 , \"55\" : 58 , \"10\" : 44 , \"31\" : 59 , \"0\" : 47 , \"32\" : 59 , \"13\" : 39 , \"56\" : 48 , \"6\" : 35 , \"12\" : 48 , \"2\" : 55 , \"57\" : 54 , \"58\" : 52 , \"8\" : 57 , \"59\" : 56 },
	"scriptDependencies": [
		"published:///js_Misc_201606220900.js",
		"published:///js_Translation_201512150845.js",
		"published:///jquery-1.11.0.min.js",
		"published:///js_Menu_201512150845.js"],
	"timings":[
		2988,
		31]}
*/

	$responseText = getDesktopData();
//	echo "\n\n".$responseText;

//	$responseText = getMobileData();
//	echo "\n\n".$responseText;

	$array = json_decode($responseText, TRUE);
	$uidl = json_decode($array['uidl'], TRUE);

	$changes = $uidl['changes'];
	echo "\n\n";
	$creationDates = [];
	foreach( $changes as $key => $value) {
		if( (count($value) > 2) && (count($value[2]) > 2) && ($value[2][2][0] == 'rows') ) {
			echo 'first row: ' .$value[2][1]['firstrow']."\n";
			echo 'total rows: ' .$value[2][1]['totalrows']."\n";
			echo 'page length: ' .$value[2][1]['pagelength']."\n";
			foreach( $value[2][2] as $rowkey => $rowvalue) {
				if( 'tr' == $rowvalue[0]) {
					$creationDates[ $rowvalue[3][1]['id'] ] = $rowvalue[2];
				}
			}
		}
	}

	$state = $uidl['state'];
	echo "\n\n";
	foreach( $state as $key => $value) {
		if( 'button_betreff' == $value['id']) {
			echo '> '.$value['caption']."\n";
		} else if( 'label_ortsangabe' == $value['id']) {
			echo '> '.$value['text']."\n";
		} else if( 'label_status-datum' == $value['id']) {
			echo '> '.$value['text']."\n\n";
		} else if( 'ams-anliegenstatus-label' == $value['styles'][0]) {
			echo '> '.$value['text']."\n";
		} else if( 'ams-meldungSearchBild' == $value['styles'][0]) {
//			echo '> '.$value['id']."\n";
			echo '> '.$value['resources']['source']['uRL']."\n";
		} else if( 'ams-anliegenstatus-image' == $value['styles'][0]) {
			echo '> '.$value['resources']['source']['uRL']."\n";
		} else if( strlen($creationDates[$key]) > 0) {
			echo '> '.$creationDates[$key]."\n";
			
		}
	}

	$data = array( 'id' => $array['v-uiId'], 'key' => $uidl['Vaadin-Security-Key'], 'syncId' => $uidl['syncId']/*, 'changes' => $changes*/ );
	return $data;
}

function fetchIssues($params)
{
	function curl_post_json( $url, $array)
	{
//		$url_ref = 'https://ordnungsamt.berlin.de';
		$ua = 'Mozilla/5.0 (Windows NT 5.1; rv:16.0) Gecko/20100101 Firefox/16.0 (FixMyStreet.Berlin robot)';
		$cookiefile = __DIR__ . '/cookiefile.txt';
		$content = json_encode($array);
		$headers = array();
		$headers[] = 'Content-Type: application/json';

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL,            $url);
//		curl_setopt( $ch, CURLOPT_REFERER,        $url_ref);
		curl_setopt( $ch, CURLOPT_HEADER,         false);
		curl_setopt( $ch, CURLOPT_USERAGENT,      $ua);
		curl_setopt( $ch, CURLOPT_COOKIEFILE,     $cookiefile);
		curl_setopt( $ch, CURLOPT_COOKIEJAR,      $cookiefile);
//		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
//		curl_setopt( $ch, CURLOPT_NOBODY,         false);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
//		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt( $ch, CURLOPT_HTTPHEADER,     $headers);
		curl_setopt( $ch, CURLOPT_POST,           true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS,     $content);

		$ret = curl_exec( $ch);
		curl_close( $ch);

		return $ret;
	}

	$url = 'https://ordnungsamt.berlin.de/frontend.mobile/UIDL/';
	$url .= '?v-uiId=' . $params['id'];

	$request = array(
		csrfToken => $params['key'],
		rpc => array(
			0 => array(
				0 => '31',
				1 => 'com.vaadin.addon.touchkit.gwt.client.vcom.GeolocatorServerRpc',
				2 => 'onGeolocationSuccess',
				3 => array(
					0 => 0,
					1 => array(
						accuracy => 45,
						altitude => null,
						altitudeAccuracy => null,
						heading => null,
						latitude => $params['latitude'],
						longitude => $params['longitude'],
						speed => null,
					),
				),
			),
		),
		syncId => $params['syncId'],
	);

	$responseText = curl_post_json( $url, $request);
	echo "\n\n".$responseText;

	// for(;;);[{"syncId": 1, "changes" : [["change",{"pid":"0"},["0",{"id":"0"}]]], "state":{}, "types":{"0":"0"}, "hierarchy":{"0":[]}, "rpc" : [], "meta" : {}, "resources" : {}, "timings":[1, 1]}]
	// for(;;);[{"syncId": 1, "changes" : [["change",{"pid":"1"},["0",{"id":"1"}]]], "state":{}, "types":{"1":"0"}, "hierarchy":{"1":[]}, "rpc" : [], "meta" : {}, "resources" : {}, "timings":[2, 1]}]
	// for(;;);[{"syncId": 1, "changes" : [["change",{"pid":"2"},["0",{"id":"2"}]]], "state":{}, "types":{"2":"0"}, "hierarchy":{"2":[]}, "rpc" : [], "meta" : {}, "resources" : {}, "timings":[4, 2]}]

	$data = $params;

	return $data;
}

$app->get('/test', function ($request, $response, $args) {
	$this->logger->addInfo('test');
	$params = $request->getQueryParams();

	$code = 200;
	$data = fetchRootConfig();
	$data['latitude'] = /*$params['latitude']*/ 52.512499399999996;
	$data['longitude'] = /*$params['longitude']*/ 13.485817899999999;

	$data = fetchIssues($data);

	$response = $response->withJson($data, $code);
	return $response;
});

$app->run();
?>
