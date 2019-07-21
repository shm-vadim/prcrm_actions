<?php

if (isset($_GET['owner']) or isset($_GET['seo']) or isset($_GET['site'])) {
    if ($owner = $_GET['owner']) {
        $line['Исполнитель по задаче'] = $owner;
    }

    if ($freelance = $_GET['fl']) {
        foreach ($freelance as $id) {
            $dt = date('Y-m-d H:i:s');
            sql_query("insert into cb_data1430 (f19500, f19490, user_id, add_time, status) values ($id, $ID, {$user['id']}, '$dt', 0)");
        }
    }

    $line['Назначен ли ответственный?'] = form_input($_GET['selected']);

    echo '<script>
window.opener.location.reload(true);
window.close();
</script>';
}

$teams = UserRepository::findTeamsByTaskData($line);
$departments = [
    'Руководитель',
    'Тех. отдел',
    'Тех. отдел (фри-ланс)',
];
$directors = $departments[0];
$users = UserRepository::findByDepartments($departments);
