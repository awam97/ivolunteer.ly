<?php

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * @var RouteCollection $routes
 */

// Default route to the login page
$routes->get('/', 'Home::Login');
$routes->get('SendNotification', 'NotificationController::sendnotification');
$routes->get('SendGroup', 'NotificationController::sendtogroup');
$routes->post('Connect', 'ApiController::connect');
$routes->get('Cities', 'ApiController::cities');
$routes->get('Admins', 'ApiController::admins');
$routes->get('Statistics', 'ApiController::statistics');
$routes->get('Activities', 'ApiController::activities');
$routes->post('webhook/wasender', 'Webhook::wasender');

$routes->group('mobile', static function ($routes) {
    $routes->get('ping', 'MobileApi::ping');
    $routes->post('login', 'MobileApi::login');
    $routes->get('me', 'MobileApi::me');
    $routes->post('refresh', 'MobileApi::refresh');
    $routes->post('logout', 'MobileApi::logout');
    $routes->get('cities', 'MobileApi::cities');
    $routes->get('activities', 'MobileApi::activities');
    $routes->get('admin/activities', 'MobileApi::adminActivities');
    $routes->get('admin/volunteers', 'MobileApi::adminVolunteers');
    $routes->get('admin/requests', 'MobileApi::adminRequests');
    $routes->post('admin/requests/status', 'MobileApi::updateRequestStatus');
    $routes->get('activities/(:num)', 'MobileApi::activity/$1');
    $routes->get('my-activities', 'MobileApi::myActivities');
    $routes->post('activities/enroll', 'MobileApi::enroll');
    $routes->post('activities/unenroll', 'MobileApi::unenroll');
});


// Dynamic routing for any controller and method
$routes->match(['get', 'post'], '(:segment)/(:segment)', function ($controller, $method) {
    // Build the fully qualified controller class name
    $controllerName = "App\\Controllers\\" . ucfirst($controller);

    // Check if the controller class exists
    if (class_exists($controllerName)) {
        
        $controllerInstance = new $controllerName();

        // Check if the method exists in the controller
        if (method_exists($controllerInstance, $method)) {
            // Call the method and return the result
            return $controllerInstance->$method();
        }

        // Method not found
        throw PageNotFoundException::forPageNotFound("Method '{$method}' not found in {$controllerName}.");
    }

    // Controller not found
    throw PageNotFoundException::forPageNotFound("Controller '{$controller}' not found.");
});

// Optional: Route to handle methods directly in the default controller (e.g., Home)
$routes->match(['get', 'post'], '(:segment)', function ($method) {
    $controllerName = "App\\Controllers\\Home";

    // Check if the method exists in the default controller
    if (method_exists($controllerName, $method)) {
        $controllerInstance = new $controllerName();

        // Call the method and return the result
        return $controllerInstance->$method();
    }

    // Method not found
    throw PageNotFoundException::forPageNotFound("Method '{$method}' not found in Home controller.");
});
