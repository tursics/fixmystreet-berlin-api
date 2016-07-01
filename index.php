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

	$ret = array(
		'pins' => array(),
		'current' => "\n\n    <li class=\"item-list__item item-list__item--empty\">\n        <p>There are no reports to show.</p>\n    </li>\n\n",
	);

	$response->write(json_encode($ret));
	return $response;
});

$app->run();
?>
