<?php

if (isset($_GET['owner']) or isset($_GET['seo']) or isset($_GET['site'])) {
    if ($owner = $_GET['owner']) {
        $line['Исполнитель по задаче'] = $owner;
    }

    if ($freelance = $_GET['fl']) {
        foreach ($freelance as $id) {
            $dt = date('Y-m-d H:i:s');
            sql_query("insert into cb_data1430 (f19500, f19490, user_id, add_time, status) values ($id, $ID, {$user['id']}, '$dt', 2)");
        }
    }

    $line['Назначен ли ответственный?'] = form_input($_GET['selected']);

    echo '<script>
window.opener.location.reload(true);
window.close();
</script>';
}

call_user_func(function () {
    $min = 60;
    $hour = 60 * $min;
    $day = 24 * $hour;
    $toUtc = -3 * $hour;

    foreach ([
                 'MIN' => $min,
                 'HOUR' => $hour,
                 'DAY' => $day,
                 'TO_UTC' => $toUtc,
                 'WORK_START' => 10 * $hour + $toUtc,
                 'LUNCH_START' => 13 * $hour + $toUtc,
                 'LUNCH_FINISH' => 13 * $hour + 30 * $min + $toUtc,
                 'WORK_FINISH' => 18 * $hour + 30 * $min + $toUtc,
             ] as $key => $val) {
        define($key, $val);
    }
});

final class DB
{
    public static function query($sql)
    {
        //echo nl2br($sql).'<hr>';
        $result = sql_query($sql);
        $arr = [];

        while ($row = sql_fetch_assoc($result)) {
            $arr[] = $row;
        }

        return $arr;
    }

    public static function value($sql)
    {
        foreach (self::query($sql) as $row) {
            foreach ($row as $val) {
                return $val;
            }
        }
    }

    public static function column($sql)
    {
        $col = [];
        foreach (self::query($sql) as $row) {
            foreach ($row as $val) {
                $col[] = $val;
                break;
            }
        }

        return $col;
    }
}

final class dt extends DateTime
{
    public static function isRealDbTime($time)
    {
        if ($time && self::createFromDBFormat($time)->setTimeZone(new DateTimeZone('UTC'))->getTimestamp() > 0) {
            return true;
        }
    }

    public static function createFromDBFormat($str)
    {
        $dt = self::createFromFormat('Y-m-d H:i:s', $str);

        return self::createFromTimestamp($dt->getTimeStamp());
    }

    public static function createFromTimestamp($time)
    {
        $dt = new self();

        return $dt->setTimestamp($time);
    }

    public function isPastOrNow()
    {
        return $this->getTimestamp() <= time();
    }

    public function __toString()
    {
        return $this->format('d.m.y в H.i');
    }

    public function date()
    {
        return $this->format('d.m.y');
    }
}

function getTablesAliasList(): array
{
    return ['u' => 'cb_users', 'g' => 'cb_groups', 't' => DATA_TABLE.'47', 'c' => DATA_TABLE.'42', 's' => DATA_TABLE.'46'];
}

function getUserDoneTime(array $tasks): \DateTimeInterface
{
    $doneTime = $currentTime = time();

    foreach ($tasks as $task) {
        if (dt::isRealDbTime($task['finish'])) {
            continue;
        }

        $taskStart = $task['start'];
        $startTime = (dt::isRealDbTime($taskStart)) ? dt::createFromDBFormat($taskStart)->getTimestamp() : $doneTime;

        while (isTimeOff($startTime)) {
            ++$startTime;
        }

        $workedTime = 0;

        while ($startTime < $currentTime) {
            if (!isTimeOff($startTime)) {
                ++$workedTime;
            }
            ++$startTime;
        }

        while ($workedTime < $task['taskDur'] * HOUR) {
            if (!isTimeOff($doneTime)) {
                ++$workedTime;
            }
            ++$doneTime;
        }
    }

    while ($doneTime !== $currentTime and isTimeOff($doneTime)) {
        ++$doneTime;
    }

    return dt::createFromTimestamp($doneTime);
}

function isTimeOff(int $time): bool
{
    $day = getDayStart($time);
    $time -= $day;

    return $time < WORK_START or $time >= WORK_FINISH
        or ($time >= LUNCH_START and $time < LUNCH_FINISH)
        or isDayOff($day);
}

function isDayOff(int $time): bool
{
    return date('N', $time) >= 6;
}

function getDayStart(int $time): int
{
    return DAY * ((int) ($time / DAY));
}

function hasTask(int $taskId, int $userId): bool
{
    extract(getTablesAliasList());

    return (bool) DB::value("select count(*) from  $t t
where  t.id = $taskId  and t.status = 0 and (t.f492 = $userId  or t.f492 = '-$userId-')");
}

function getUserTasks(int $userId): array
{
    extract(getTablesAliasList());
    $sql = "select 
    cp.f444 as domain, 
    cp1.f444 as oldDomain,
    t.f9761 as shortDesc, 
    t.id as taskId, 
    t.f5811 as task,
    t.f20260 as taskTarget,
    t.f499 as taskDur, 
    t.f18060 as start, 
    t.f504 as finish,  
    c.f435 as company 
    from  $t t
    left join $c c on(t.f1067 = c.id)
    left join $c cp on(t.f17470 = cp.id)
    left join $c cp1 on(t.f20250 = cp1.id)
    where  t.status = '0' and t.f501 != 'Да' and (t.f492 = '$userId' or t.f492 = '-{$userId}-')
    order by t.id asc";
    $arr = DB::query($sql);

    foreach ($arr as $k => $t) {
        $t['domain'] = $t['domain'] ?: $t['oldDomain'];
        $t['taskDur'] = (float) DB::value("select sum(w.f18450) as s from cb_data47 t
join cb_data471 w on(t.id = w.f5461)
where t.id = {$t['taskId']} and w.status = 0 and w.f18350 in('Осн.задача', 'Доработка', 'Мелкие правки') and w.f18460 != 'Готово'");

        $arr[$k] = $t;
    }

    return $arr;
}

function getUsersByDepartments(array $departments): array
{
    extract(getTablesAliasList());
    $departmentsSQLList = sprintf("'%s'", implode("', '", $departments));

    $sql = "select u.id from $s s
join $u u on(s.f483 = u.id)
WHERE u.arc = 0 and s.f5841 in ($departmentsSQLList)";

    $userIdList = [];
    foreach (DB::query($sql) as $row) {
        $userIdList[] = $row['id'];
    }

    return getUsersByIdList($userIdList);
}

function getUsersByIdList(array $idList): array
{
    extract(getTablesAliasList());
    if (!$idListSQLString = implode(', ', $idList)) {
        $idListSQLString = 'null';
    }

    $sql = "select
s.id,
u.fio as `name`,
s.f484 as post,
s.f18340 as postComment,
s.f5841 as dept,
s.f6151 as status,
s.f18070 as reason,
s.f5861 as toWhat,
s.f18080 as techTasksAct
from $s s
join $u u on(s.f483 = u.id)
join $g g on(u.group_id = g.id)
where u.id in ($idListSQLString)
order by u.fio asc";

    $users = [];
    foreach (DB::query($sql) as $user) {
        $userId = $user['id'];
        $tasks = getUserTasks($userId);
        $user['tasks'] = $tasks;
        $toWhat = $user['toWhat'];
        $user['toWhat'] = (dt::isRealDbTime($toWhat)) ? dt::createFromDBFormat($toWhat) : null;
        $estimated = 0;

        foreach ($tasks as $task) {
            if ($task['taskDur']) {
                ++$estimated;
            }
        }

        $user['estTasks'] = $estimated;
        $users[$userId] = $user;
    }

    return sortUsersByGroups($users);
}

function sortUsersByGroups(array $users): array
{
    $groups = array_flip(explode("\n", '
Дизайнер
free-lance. Оклад: Дизайнеры
free-lance. Позадачно: Дизайнеры
Верстальщик
free-lance. Оклад: Верстальщики
free-lance. Позадачно: Верстальщики
Программист
free-lance. Оклад: Программисты
free-lance. Позадачно: Программисты
Тестировщик
free-lance. Оклад: Тестировщики
free-lance. Позадачно: Тестировщики
Оптимизатор
Оптимизатор SK
Копирайтер
Копирайтер SK
Контент-менеджер
Контент-менеджер SK
Веб-аналитик
Веб-аналитик SK
Веб-мастер
Веб-мастер SK
Маркетолог
Маркетолог SK
Руководитель отдела разработок
Руководитель SEO - отдела
'));
    unset($groups['']);

    foreach ($users as $user) {
        $userPost = $user['post'];

        if (!is_array($groups[$userPost])) {
            $groups[$userPost] = [];
        }

        $groups[$userPost][] = $user;
    }

    $users = [];
    foreach ($groups as $group) {
        foreach ($group as $user) {
            $users[] = $user;
        }
    }

    return $users;
}

/**
 * @param int|null $companyId
 */
function getTeams($companyId): array
{
    if (null === $companyId) {
        return ['site' => [], 'seo' => []];
    }

    extract(getTablesAliasList());
    $result = DB::query("select 
c.f6181 as seo, 
c.f6171 as site 
from $c c 
where c.id = $companyId");

    if ($row = $result[0]) {
        $site = getUsersByList($row['site']);
        $seo = getUsersByList($row['seo']);
    } else {
        $site = $seo = [];
    }

    return ['site' => $site, 'seo' => $seo];
}

function getUsersByList(string $idListString): array
{
    $processedIdListString = preg_replace(
        ['#^-#', '#-$#', '#-#'],
        ['', '', "\r\n"],
        $idListString
    );

    return getUsersByIdList(explode("\r\n", $processedIdListString));
}

function getDepartments(): array
{
    return [
        'Руководитель',
        'Тех. отдел',
        'Фри-ланс отдел',
    ];
}

$teams = getTeams($line['По компании']['raw']);
$departments = getDepartments();
$directors = $departments[0];
$users = getUsersByDepartments($departments);
?>

<!doctype html>

<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Кто делает задачу</title>
    <style>
        * {
            margin: 0;
        }

        table {
            width: 80%;
            margin: 0 auto;
            border-collapse: collapse;
        }

        td, th {
            padding: 4px;
            border: 1px solid black;
            text-align: center;
            vertical-align: middle;
        }

        input[type=radio] {
            border: 1px solid black;
        }

        input[type=checkbox] {
            background-color: red;
        }

        form {
            margin-bottom: 50px;
        }

        form > div {
            margin-top: 20px;
        }

        .teams > div {
            float: left;
            margin-left: 3px;
        }

        .teams {
            overflow: auto;
        }

        .post-comment {
            font-size: 12px;
            font-style: italic;
            color: #111;
        }

        .submit-button {
            display: block;
            margin: 0 auto;
        }
    </style>
</head>

<body>
<form>
    <input type='hidden' name='id' value='<?= $button_id; ?>'>
    <input type='hidden' name='line_id' value='<?= $ID; ?>'>
    <input type="hidden" name="selected" value="Да">

    <div class="teams">
        <?php foreach (['seo' => 'Команда этого проекта SEO', 'site' => 'Команда проекта САЙТ'] as $key => $title) : ?>
            <div>
                <h2><?= $title; ?></h2>
                <?php foreach ($teams[$key] as $user) : ?>
                    <?= $user['name']; ?><br>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div>
            <div>
                <a href="#" class="show-inst">*</a>
                <div>
                    <p>
                        При определении исполнителя по новой задаче ориентируемся на кол-во задач в работе по каждому
                        сотруднику и ориентировочную дату, указанную в поле "когда освободится".
                    </p>

                    <p>
                        Поле "Когда освободится" высчитывается исходя из ориентировочной длительности выполнения
                        (указанной в поле Time (ч)). <br>
                        Если исполнитель еще не оценил задачу/доработку по времени выполнения, то ее время не учтется в
                        формуле поля "Когда свободен".
                    </p>

                    <p>
                        Поэтому при распределении задач смотрим не только на дату "когда освободится", но также
                        учитываем, ВСЕ ли поставленные задачи оценены по времени.
                    </p>

                    <p>
                        Если в поле "Всего задач в работе/оценено" стоит 7/7 - это значит, что все поставленные задачи
                        оценены и время "Когда освободится" точное.
                    </p>

                    <p>
                        Если в поле "Всего задач в работе/оценено" стоит 7/3 - это значит, из семи поставленных задач
                        оценено только 3, следовательно поле "когда освободиться" не точно, еще 4 задачи не оценены
                        исполнителем. В данном случае необходимо попросить данного исполнителя оценить задачи, что еще
                        не оценены. (Задачи должны оцениваться регулярно, каждый день, чтобы можно было правильно все
                        распределять и учитывать максимально точное время готовности.)
                    </p>

                    <p>
                        В первую очередь задачи назначаются сотрудникам в офисе. Если все в офисе загружены, то
                        подключаем исполнителей на фри-лансе. В случае если нужно подключить фри-ланс исполнителя, то
                        данную задачу мы адресуем не конкретному исполнителю, а сразу всем, кто по своей специализации
                        может выполнить данную задачу. Данные специалисты ознакомятся с задачей и те, кто сможет в
                        ближайшее время к ней приступить, напишут свои сроки в подтаблице к данной задаче "Оценка
                        сроков". Уведомления о факте ответов фри-ланс исполнителей будут отправлены на почту. Адресуем
                        задачу тому, кто быстрее всех готов приступить к выполнению и называет самые короткие сроки. Или
                        тому, кто первый ответил и готов начать в ближайшее время. Как только исполнитель понятен, в ДД
                        "Кто делает задачу" снимаем галки выполнения задачи с других, кто получил задачу на оценку
                        сроков". Например, поставили трем исполнителям задачу, отписался 1, с двух других галки снимаем,
                        чтобы задача закрепилась за первым. "
                    </p>

                    <p>
                        ! После выбора ответственного необходимо зайти в задачу и проверить, чтобы в поле "ИСПОЛНИТЕЛЬ"
                        отображалась фамилия выбранного специалиста.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="owner">
        <h2>Ответственный по данной задаче:</h2>

        <table>
            <thead>
            <tr>
                <th colspan="2">Специалист</th>
                <th>Когда освободится</th>
                <th>Всего задач <br> в работе/оценено</th>
                <th>Какие именно?</th>
                <th colspan="2">Исполнитель</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user) : ?>
                <tr>
                    <?php extract($user); ?>
                    <td>
                        <a href="/view_line2.php?table=46&line=<?= $id; ?>"><?= $name; ?></a>
                    </td>
                    <td>
                        <?= $post; ?><br>
                        <span class="post-comment"><?= $postComment; ?></span>
                    </td>
                    <?php $doneTime = getUserDoneTime($tasks); ?>
                    <td><?= ($doneTime->isPastOrNow()) ? 'Свободен' : $doneTime; ?></td>
                    <td><?= count($tasks).'/'.$estTasks; ?></td>
                    <td>
                        <?php if ($dept === $directors) : ?>

                        <?php elseif (!$tasks) : ?>
                            -
                        <?php else : ?>
                            <a class="show-tasks-list" href="#">*</a>

                            <ol>
                                <?php foreach ($tasks as $task) : ?>
                                    <?php extract($task); ?>
                                    <li><a href="/view_line2.php?table=47&filter=53&edit_mode=on&line=<?= $taskId; ?>"
                                           target="blank"><?= "$taskTarget - $domain - $shortDesc"; ?></a></li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ('Задачи не ставим' !== $status) : ?>
                            <input type="radio" name="owner"
                                   value="<?= $id; ?>" <?= hasTask($ID, $id) ? 'checked' : ''; ?>>
                        <?php else : ?>
                            Задачи не ставим<br>
                            <a class="show-details" href="#">*</a>

                            <ul>
                                <li>Причина: <?= $reason; ?></li>
                                <li>До: <?= null !== $toWhat ? $toWhat->date() : '-'; ?></li>
                                <li>Тек. задачи: <?= $techTasksAct; ?></li>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ('Фри-ланс отдел' === $dept) : ?>
                            <input type="checkbox" name="fl[]" value="<?= $id; ?>">
                        <?php else : ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <hr>
    <input type="submit" class="submit-button" value="Сохранить">
</form>
<a href="/view_line2.php?table=47&filter=53&line=<?= $ID; ?>">Назад к задаче</a>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript">
    "use strict";

    var toggleDisplay = new function () {
        var constructor = function (data) {
            for (var key in data) {
                this[key] = data[key];
            }

            this.sources.click(this.toggle.bind(this));
            this.hide();
        }

        var p = constructor.prototype;

        p.show = function (source) {
            source = source || this.sources;
            var target = this.getTarget(source);
            target.css('display', '');
            this.onShow(source, target);
        }

        p.hide = function (source) {
            source = source || this.sources;
            var target = this.getTarget(source);
            target.css('display', 'none');
            this.onHide(source, target);
        }

        p.toggle = function (event) {
            event.preventDefault();

            var source = $(event.target);
            var target = this.getTarget(source);
            ((target.css('display') == 'none') ? this.show : this.hide).call(this, source, target);
        }

        p.onShow = function (source) {
            source.html('Скрыть');
        }

        p.onHide = function (source) {
            source.html('Показать');
        }

        p.getTarget = function (source) {
            return source.siblings('div, p, ul, ol');
        }

        return constructor;
    }

    new toggleDisplay({
        sources: $('.show-tasks-list')
    });

    new toggleDisplay({
        sources: $('.show-details'),

        onHide: function (link) {
            link.html('Подробнее');
        },
    });

    var needOwnerClass = 'need-owner';
    var radioButtons = $('[type=radio]');
    var submitButton = $('.submit-button').addClass(needOwnerClass);

    radioButtons.click(function (event) {
        submitButton.removeClass(needOwnerClass);
    });

    submitButton.click(function (event) {
        if (submitButton.hasClass(needOwnerClass)) {
            event.preventDefault();
            alert('Исполнитель не выбран');
        }
    });

    new toggleDisplay({
        sources: $('.show-memo'),

        onHide: function (link) {
            link.html('Показать памятку');
        },

        onShow: function (link) {
            link.html('Скрыть памятку');
        },
    });

    new toggleDisplay({
        sources: $(".show-inst"),

        onHide: function (link) {
            link.html("       Инструкция для<br>распределяющего задачи");
        },
    });
</script>
</body>
</html>