<?php
// API JSON
//   GET                 : liste des tâches
//   POST                : ajouter une tâche
//   PUT                 : modifier une tâche (titre, faite, priorite, categorie, echeance)
//   DELETE ?id=N        : supprimer une tâche
//   DELETE ?terminees=1 : supprimer toutes les tâches terminées
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

function reponse($data, int $code = 200 void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function erreur(string $message): void {
    reponse(['erreur' => $message], 400);
}

// Valide les champs présents dans $body et renvoie [colonne => valeur] prêt pour SQL.
function champs(array $body): array {
    $out = [];

    if (array_key_exists('titre', $body)) {
        $titre = trim((string)$body['titre']);
        if ($titre === '' || mb_strlen($titre) > 200) {
            erreur('Le titre doit contenir entre 1 et 200 caractères.');
        }
        $out['titre'] = $titre;
    }
    if (array_key_exists('priorite', $body)) {
        $p = (int)$body['priorite'];
        if ($p < 0 || $p > 2) erreur('Priorité invalide.');
        $out['priorite'] = $p;
    }
    if (array_key_exists('categorie', $body)) {
        $c = trim((string)$body['categorie']);
        if (mb_strlen($c) > 50) erreur('La catégorie ne peut pas dépasser 50 caractères.');
        $out['categorie'] = $c === '' ? null : $c;
    }
    if (array_key_exists('echeance', $body)) {
        $e = trim((string)($body['echeance'] ?? ''));
        if ($e === '') {
            $out['echeance'] = null;
        } else {
            $d = DateTime::createFromFormat('Y-m-d', $e);
            if (!$d || $d->format('Y-m-d') !== $e) erreur('Date invalide.');
            $out['echeance'] = $e;
        }
    }
    if (array_key_exists('faite', $body)) {
        $out['faite'] = empty($body['faite']) ? 0 : 1;
    }
    return $out;
}

try {
    $pdo    = db();
    $method = $_SERVER['REQUEST_METHOD'];
    $body   = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) $body = [];

    switch ($method) {
        case 'GET':
            $sql = 'SELECT id, titre, faite, priorite, categorie, echeance, cree_le
                    FROM taches ORDER BY id DESC';
            reponse($pdo->query($sql)->fetchAll());

        case 'POST':
            $c = champs($body);
            if (!isset($c['titre'])) erreur('Le titre est obligatoire.');
            $pdo->prepare('INSERT INTO taches (titre, priorite, categorie, echeance) VALUES (?, ?, ?, ?)')
                ->execute([
                    $c['titre'],
                    $c['priorite'] ?? 1,
                    $c['categorie'] ?? null,
                    $c['echeance'] ?? null,
                ]);
            reponse(['id' => (int)$pdo->lastInsertId()], 201);

        case 'PUT':
            $id = (int)($body['id'] ?? 0);
            if ($id <= 0) erreur('Identifiant manquant.');
            $c = champs($body);
            if (!$c) erreur('Rien à modifier.');
            if (isset($c['faite'])) {
                $c['terminee_le'] = $c['faite'] ? date('Y-m-d H:i:s') : null;
            }
            // Les noms de colonnes viennent de champs() (liste fixe), jamais de l'utilisateur.
            $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($c)));
            $pdo->prepare("UPDATE taches SET $set WHERE id = ?")
                ->execute([...array_values($c), $id]);
            reponse(['ok' => true]);

        case 'DELETE':
            if (!empty($_GET['terminees'])) {
                $n = $pdo->exec('DELETE FROM taches WHERE faite = 1');
                reponse(['supprimees' => $n]);
            }
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) erreur('Identifiant manquant.');
            $pdo->prepare('DELETE FROM taches WHERE id = ?')->execute([$id]);
            reponse(['ok' => true]);

        default:
            reponse(['erreur' => 'Méthode non autorisée.'], 405);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());

    reponse([
        'erreur' => 'Une erreur interne est survenue.'
    ], 500);
}

