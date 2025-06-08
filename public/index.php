<?php
declare(strict_types=1);

// Ensure this path is correct relative to your project structure
require __DIR__ . '/../vendor/autoload.php';

// Basic error display for development. Do NOT use in production.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Simple .env loader (replace with a proper library like vlucas/phpdotenv in a real app)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value); // Set $_ENV not getenv() directly for this simple script
        }
    }
}

// Database connection parameters from environment variables or defaults
// Using TEST variables as those were used in Phinx config and DatabaseTestCaseHelper
$dbHost = $_ENV['DB_HOST_TEST'] ?? '127.0.0.1';
$dbName = $_ENV['DB_NAME_TEST'] ?? 'test_condo_management'; // Match Phinx 'testing' or DatabaseTestCaseHelper
$dbUser = $_ENV['DB_USER_TEST'] ?? 'testuser';
$dbPass = $_ENV['DB_PASS_TEST'] ?? 'testpass';
$dbPort = $_ENV['DB_PORT_TEST'] ?? '5432';

// Instantiate the ServiceFactory
try {
    $factory = new App\Infrastructure\ServiceFactory($dbHost, $dbName, $dbUser, $dbPass, (string)$dbPort);
} catch (\PDOException $e) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed during factory setup.', 'details' => $e->getMessage()]);
    // Optionally log $e->getMessage() to a secure log file
    error_log("Initial DB connection error: " . $e->getMessage());
    exit;
}


// --- Super Basic Router ---
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($requestUri, PHP_URL_PATH);

header('Content-Type: application/json');

try {
    if ($path === '/condominiums' && $requestMethod === 'POST') {
        // Example: Register a new condominium
        // In a real app, parse JSON body: $input = json_decode(file_get_contents('php://input'), true);
        // For simplicity, using hardcoded or GET params if testing via browser
        // Assuming application/x-www-form-urlencoded or query params for this basic example
        $input = $_POST ?: $_GET;

        $name = $input['name'] ?? null;
        $street = $input['street'] ?? null;
        $city = $input['city'] ?? null;
        $postalCode = $input['postalCode'] ?? null;
        $country = $input['country'] ?? null;

        if (!$name || !$street || !$city || !$postalCode || !$country) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields for condominium registration. Required: name, street, city, postalCode, country.']);
            exit;
        }

        $controller = $factory->getCondominiumController();
        list($data, $statusCode) = $controller->registerCondominium($name, $street, $city, $postalCode, $country);
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);

    } elseif (preg_match('#^/condominiums/([a-fA-F0-9\-]+)$#', $path, $matches) && $requestMethod === 'GET') {
        $condoId = $matches[1];
        $controller = $factory->getCondominiumController();
        list($data, $statusCode) = $controller->getCondominiumDetails($condoId);
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);

    } elseif (preg_match('#^/units/([a-fA-F0-9\-]+)/statement$#', $path, $matches) && $requestMethod === 'GET') {
        $unitId = $matches[1];
        $controller = $factory->getUnitController();
        list($data, $statusCode) = $controller->getStatement($unitId);
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);

    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found', 'path' => $path, 'method' => $requestMethod]);
    }

} catch (\PDOException $e) {
    http_response_code(503); // Service Unavailable (DB connection)
    echo json_encode(['error' => 'Database operation error.', 'details' => $e->getMessage()]);
    $factory->getLogger()->error("PDOException in router: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
} catch (\DomainException $e) { // Catch specific domain errors (like "Not Found" from handlers)
    // Determine status code based on exception message or type if more specific exceptions are used
    $statusCode = 404; // Default for DomainException, could be 400 or 422
    if (stripos($e->getMessage(), 'not found') !== false) {
        $statusCode = 404;
    } elseif (stripos($e->getMessage(), 'invalid') !== false || stripos($e->getMessage(), 'format') !== false) {
        $statusCode = 400;
    }
    http_response_code($statusCode);
    echo json_encode(['error' => $e->getMessage()]);
    $factory->getLogger()->notice("DomainException in router: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
} catch (\InvalidArgumentException $e) {
    http_response_code(400); // Bad request
    echo json_encode(['error' => $e->getMessage()]);
    $factory->getLogger()->warning("InvalidArgumentException in router: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
} catch (\Throwable $e) {
    http_response_code(500);
    $errorDetails = ['error' => 'An unexpected server error occurred.'];
    // Add more details if in a non-production environment
    // if (getenv('APP_ENV') !== 'production') {
    //    $errorDetails['exception_type'] = get_class($e);
    //    $errorDetails['details'] = $e->getMessage();
    //    $errorDetails['trace'] = $e->getTraceAsString(); // Be careful with exposing trace
    // }
    echo json_encode($errorDetails);
    $factory->getLogger()->error("Unhandled Throwable in router: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
}
