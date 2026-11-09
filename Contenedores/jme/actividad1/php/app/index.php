<?php

class Database{
    private string $host;
    private string $port;
    private string $user;
    private string $password;
    private string $database;

    public function __construct(){
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->port = getenv('DB_PORT') ?: '5432';
        $this->user = getenv('DB_USER') ?: '';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->database = getenv('DB_NAME') ?: '';
    }

    public function getConnection(): PDO {
        $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
        try {
            return new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Postgres connect error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Database connection failed"]);
            exit;
        }
    }
}

class Todos {
    private string $todosTable = "todos";
    public ?int $id = null;
    public ?string $title = null;
    public ?bool $done = null;
    public ?string $lang = null;
    public ?string $created_at = null;
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function read(): array
    {
        if ($this->id) {
            $stmt = $this->conn->prepare("SELECT id, title, done, lang, created_at FROM {$this->todosTable} WHERE id = :id");
            $stmt->execute([':id' => $this->id]);
        } else {
            $stmt = $this->conn->prepare("SELECT id, title, done, lang, created_at FROM {$this->todosTable} ORDER BY id");
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public function create(): ?array
    {
        $this->lang = 'php';
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->todosTable} (title, lang)
         VALUES (:title, :lang)
         RETURNING id, title, done, lang, created_at"
        );
        try {
            $stmt->execute([':title' => $this->title, ':lang' => $this->lang]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Insert error: " . $e->getMessage());
            return null;
        }
    }
}

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$request = explode("/", trim($_SERVER['PATH_INFO'] ?? '', "/"));
$resource = $request[0] ?? null;
$id = $request[1] ?? null;

require_once __DIR__ . '/controller.php';

if ($resource !== 'todos') {
    http_response_code(404);
    echo json_encode(["error" => "Resource not found"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$todos = new Todos($db);

handleRequest($method, $resource, $todos);