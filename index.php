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

	function getBrowserDetailsParameters()
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

	$url = 'https://ordnungsamt.berlin.de/frontend.mobile/';
	// Timestamp to avoid caching
	$url .= '?v-' . (time() * 1000);

	$params = 'v-browserDetails=1';
	$params .= '&theme=mobile';
	$params .= '&v-appId=' . 'frontendmobile-1035679436';
	$params .= '&' . getBrowserDetailsParameters();

	$responseText = curl_post( $url, $params);

	$array = json_decode($responseText, TRUE);
	$security = json_decode($array['uidl'], TRUE);

	$data = array( 'id' => $array['v-uiId'], 'key' => $security['Vaadin-Security-Key'], 'syncId' => $security['syncId'] );
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
	echo $responseText;

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
