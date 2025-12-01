<?php
require_once __DIR__ . '/db.php';

function bind_params_stmt($stmt, &$params){
    if (empty($params)) return;
    $types = '';
    foreach ($params as $p){
        if (is_int($p)) $types .= 'i';
        elseif (is_float($p) || is_double($p)) $types .= 'd';
        else $types .= 's';
    }
    $refs = [];
    $refs[] = & $types;
    foreach ($params as $k => &$v) $refs[] = & $v;
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function db_query_all($sql, $params = []){
    $conn = db_connect();
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) throw new Exception(mysqli_error($conn));
    if (!empty($params)) bind_params_stmt($stmt, $params);
    if (!mysqli_stmt_execute($stmt)) { $err = mysqli_stmt_error($stmt); mysqli_stmt_close($stmt); throw new Exception($err); }
    $res = mysqli_stmt_get_result($stmt);
    if ($res === false) { mysqli_stmt_close($stmt); return []; }
    $rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function db_query_one($sql, $params = []){
    $rows = db_query_all($sql, $params);
    return count($rows) ? $rows[0] : null;
}

function db_execute($sql, $params = []){
    $conn = db_connect();
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) throw new Exception(mysqli_error($conn));
    if (!empty($params)) bind_params_stmt($stmt, $params);
    if (!mysqli_stmt_execute($stmt)) { $err = mysqli_stmt_error($stmt); mysqli_stmt_close($stmt); throw new Exception($err); }
    $insert_id = mysqli_insert_id($conn);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return ['success' => true, 'insert_id' => $insert_id, 'affected_rows' => $affected];
}

function db_begin(){ mysqli_begin_transaction(db_connect()); }
function db_commit(){ mysqli_commit(db_connect()); }
function db_rollback(){ mysqli_rollback(db_connect()); }
?>
