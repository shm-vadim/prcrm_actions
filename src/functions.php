<?php

function dump($var)
{
    var_dump($var);

    return $var;
}

function dd($var)
{
    dump($var);
    die();
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

function getDepartments(): array
{
    return [
        'Руководитель',
        'Тех. отдел',
        'Фри-ланс отдел',
    ];
}
