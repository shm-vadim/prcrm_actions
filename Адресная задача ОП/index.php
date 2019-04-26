<?php

if (isset($_GET['owner'])) {
    if ($owner = $_GET['owner']) {
        $line['Исполнитель по задаче'] = '-'.implode('-', $owner).'-';
    }
    $line['Назначен ли ответственный?'] = form_input($_GET['selected']);

    echo '<script>
window.opener.location.reload(true);
window.close();
</script>';
}

$users = UserRepository::findByDepartments([
    'Руководитель',
    'Отдел продаж',
    'Отдел продаж (фри-ланс)',
    'Бух.отдел',
]);
