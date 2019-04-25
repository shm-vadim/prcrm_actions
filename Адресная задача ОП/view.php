<!doctype html>

<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Адресная задача (Отдел продаж)</title>
</head>
<body>
<form method='get' style="margin:0; margin-bottom:-20px">
    <input type='hidden' name='id' value='<?= $button_id; ?>'>
    <input type='hidden' name='line_id' value='<?= $ID; ?>'>
    <input type="hidden" name="selected" value="Да">

    <h2>Ответственный по данной задаче:</h2>
    <table>
        <?php foreach ($users as $user) : ?>
            <?php extract($user); ?>
            <tr>
                <td>
                    <input type='checkbox' name='owner[]' value='<?= $id; ?>'>
                </td>
                <td>
                    <a href="/view_line2.php?table=46&line=<?= $id; ?>"><?= $name; ?></a>
                </td>
                <td>
                    <?= $post; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <br><br>
    <input type='submit' value='Сохранить'>
</form>
</body>
</html>