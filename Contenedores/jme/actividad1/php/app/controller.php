<?php

function formatTodo(array $row): array
{
    return [
        "id" => (int) $row['id'],
        "title" => $row['title'],
        "done" => (bool) $row['done'],
        "lang" => $row['lang'],
        "created_at" => $row['created_at'],
    ];
}

function handleRequest($method, $resource, $todos)
{
    switch ($method) {
        case 'GET':
            $items = [];

            foreach ($todos->read() as $todo) {
                $items[] = [
                    "id" => (int) $todo['id'],
                    "title" => $todo['title'],
                    "done" => (bool) $todo['done'],
                    "lang" => $todo['lang'],
                    "created_at" => $todo['created_at'],
                ];
            }

            http_response_code(200);
            echo json_encode(["todos" => $items]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents("php://input"), true);
            $title = trim($input['title'] ?? '');

            if ($title === '') {
                http_response_code(400);
                echo json_encode(["error" => "title es requerido"]);
                break;
            }
            $todos->title = $title;
            $created = $todos->create();
            if ($created === null) {
                http_response_code(500);
                echo json_encode(["error" => "Unable to create todo."]);
                break;
            }
            http_response_code(201);
            echo json_encode(formatTodo($created));
            break;
        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
    }
}