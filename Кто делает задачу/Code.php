if (isset($_GET["owner"]) or isset($_GET["seo"]) or isset($_GET["site"]) ){
if ($owner=$_GET['owner']) $line['Исполнитель по задаче'] = $owner;
if ($fl=$_GET["fl"]) {
foreach ($fl as $id) {
$dt=date("Y-m-d H:i:s");
sql_query("insert into cb_data1430 (f19500, f19490, user_id, add_time, status) values ($id, $ID, {$user["id"]}, '$dt', 2)");
}
}
//if ($site=$_GET["site"]) $line['Команда этого проекта SITE ']=implode("\r\n",$site);
//if ($seo=$_GET["seo"]) $line['Команда этого проекта SEO'] = implode("\r\n",$seo);
$line['Назначен ли ответственный?'] = form_input($_GET['selected']);

echo "<script>
window.opener.location.reload(true);
window.close();
</script>";
}

function isTimeOff($time) {
$day=getDayStart($time);
$time-=$day;

return $time<WORK_START or $time >= WORK_FINISH 
or ($time >= LUNCH_START and $time < LUNCH_FINISH)
or isDayOff($day);
    }
    
    function counter($name) {
    static $i=0;
    $limit=1000000;
    if (++$i>=$limit) {
    echo $limit;
    stop($name);
    }
    }
    
    function getUserDoneTime($tasks) {
    $doneTime=$curTime=time();
    
    foreach ($tasks as $task) {
    if (dt::isRealDbTime($task['finish'])) continue;

    $taskStart=$task['start'];
    $startTime=(dt::isRealDbTime($taskStart)) ? dt::createFromDb($taskStart)->getTimestamp() : $doneTime;
    while (isTimeOff($startTime)) {
    $startTime++;
    }
    
    $workedTime=0;
    while ($startTime<$curTime) {
    if (!isTimeOff($startTime)) $workedTime++;
$startTime++;
    }
    
    while ($workedTime<$task['taskDur']*HOUR) {
    if (!isTimeOff($doneTime)) $workedTime++;
$doneTime++;
    }
}

while ($doneTime != $curTime and isTimeOff($doneTime)) {
$doneTime++;
}

return dt::createFromTimestamp($doneTime);
}

function stop($var) {
die(var_dump($var));
}

function defineTimeConsts() {
$min=60;
$hour=60*$min;
$day=24*$hour;
$toUtc=-3*$hour;

foreach(array(
'MIN'=>$min,
'HOUR'=>$hour,
'DAY'=>$day,
'TO_UTC'=>$toUtc,
'WORK_START'=>10*$hour+$toUtc,
'LUNCH_START'=>13*$hour+$toUtc,
'LUNCH_FINISH'=>13*$hour+30*$min+$toUtc,
'WORK_FINISH'=>18*$hour+30*$min+$toUtc,
) as $key=>$val) {
define($key, $val);
}
}

defineTimeConsts();

function isDayOff($time) {
return date('N', $time) >= 6;
}

function getUserTasks($userId) {
extract(t());
$arr=dbQuery("select $t.f17470 as domain, $t.f9761 as shortDesc, $t.id as taskId, $t.f5811 as task, $t.f499 as taskDur, $t.f18060 as start, $t.f504 as finish,  $c.f435 as company from  $t 
join $c on($t.f1067 = $c.id)
where  $t.status = 0 and $t.f501 != 'Да' and ($t.f492 = $userId or $t.f492 = '-$userId-')
order by $t.id asc");

foreach ($arr as $k=>$t) {
$res=sql_fetch_assoc(sql_query("select sum(w.f18450) as s from cb_data47 t
join cb_data471 w on(t.id = w.f5461)
where t.id = {$t["taskId"]} and w.status = 0 and w.f18350 in('Осн.задача', 'Доработка', 'Мелкие правки') and w.f18460 != 'Готово'"));

$t["taskDur"]=$res["s"];
$arr[$k]=$t;
}

return $arr;
}

function getDayStart($time=null) {
if ($time === null) $time=time();
return DAY * ((int) ($time / DAY));
}

class dt extends DateTime{

public static function isRealDbTime($time) {
if ($time && self::createFromDb($time)->setTimeZone(new DateTimeZone('UTC'))->getTimestamp() > 0) return true;
}

public static function createFromDb($str) {
$dt=self::createFromFormat('Y-m-d H:i:s', $str);
return self::createFromTimestamp($dt->getTimeStamp());
}

public static function createFromTimestamp($time) {
$dt= new self();
return $dt->setTimestamp($time);
}

public function isPastOrNow() {
return $this->getTimestamp() <= time();
}

public function __toString() {
return $this->format('d.m.y в H.i');
}

public function date() {
return $this->format('d.m.y');
}

}

function t() {
return array('u'=>'cb_users', 'g'=>'cb_groups', 't'=>DATA_TABLE.'47', 'c'=>DATA_TABLE.'42', 's'=>DATA_TABLE.'46');
}

function getUsersByDepts($deptsNames) {
$deptsArr=(is_array($deptsNames)) ? $deptsNames : array($deptsNames);
extract(t());
$depts=sprintf("'%s'", implode("', '", $deptsArr));

$sql="select $u.id from $s
join $u on($s.f483=$u.id)
WHERE $u.arc = 0 and $s.f5841 in ($depts)";

$ids=array();
foreach (dbQuery($sql) as $row) {
$ids[]=$row['id'];
}

return getUsersByIds($ids);
}

function getUsersByIds($ids) {
extract(t());
if (!$idsStr=implode(', ', $ids)) $idsStr='null';

$sql="select
$u.id,
$u.fio as `name`,
$s.f484 as post,
$s.f18340 as postComment,
$s.f5841 as dept,
$s.f6151 as status,
$s.f18070 as reason,
$s.f5861 as toWhat,
$s.f18080 as techTasksAct
from $s
join $u on($s.f483=$u.id)
join $g on($u.group_id = $g.id)
where $u.id in ($idsStr)
order by $u.fio asc";

$users=array();
foreach(dbQuery($sql) as $user) {
$userId=$user['id'];
$tasks=getUserTasks($userId);
$user['tasks']=$tasks;
$tw=$user['toWhat'];
$user['toWhat']=(dt::isRealDbTime($tw)) ? dt::createFromDb($tw) : '';
$users[$userId]=$user;
}

return sortUsersBy($users);
}

function sortUsersBy($arr) {
$groups=array_flip(explode("\n", "
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
"));//
unset($groups['']);

foreach ($arr as $user) {
$gn=$user['post'];
if (!is_array($groups[$gn])) $groups[$gn]=array();
$groups[$gn][]=$user;
}

$users=array();
foreach ($groups as $group) {
foreach ($group as $user) {
$users[]=$user;
}
}

return $users;
}

function dbQuery($sql) {
//echo nl2br($sql).'<hr>';
$result=sql_query($sql);
$arr=array();

while ($row=sql_fetch_assoc($result)) {
$arr[]=$row;
}

return $arr;
}

function getTeams($comId) {
extract(t());
if (!$comId) $comId='null';
$res= dbQuery("select $c.f6181 as seo, $c.f6171 as site from $c where $c.id = $comId");

if ($row=$res[0]) {
$site=getUsersByList($row['site']);
$seo=getUsersByList($row['seo']);
} else $site=$seo=array();

return array('site'=>$site, 'seo'=>$seo);
}

function getUsersByList($str) {
$pStr=preg_replace(array('#^-#', '#-$#', '#-#'), 
array('', '', "\r\n"), 
$str);
return getUsersByIds(explode("\r\n", $pStr));
}

function getDepts() {
return array(
'Руководитель',
'Тех. отдел',
'Фри-ланс отдел',
);
}

$teams=getTeams($line['По компании']['raw']);
$depts=getDepts();
$directors=$depts[0];
$users=getUsersByDepts($depts);
//die
(json_encode(array(
'teams'=>$teams,
'users'=>$users,
)));
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Кто делает задачу</title>
<style>
.post-comment {
font-size: 12px;
font-style: italic;
color: #111;
}

input[type=radio] {
border: 1px solid black;
}

* {
margin: 0;
}

[type=checkbox] {
background-color: red;
}

table { 
border-collapse: collapse;
}

td, th { 
padding: 4px;
border: 1px solid black;
text-align: center;
vertical-align: middle;
}

form > div {
margin-top: 20px;
}

.teams > div {
float: left;
margin-left: 3px;
}

.owner {
margin-left: 40px;
}

.teams {
overflow: auto;
}

.submit-button {
display: block;
margin: 0 auto;
}

form {
margin-bottom: 50px;
}
</style>
</head>

<body>
<form>
<input type='hidden' name='id' value='<?= $button_id ?>'>
<input type='hidden' name='line_id' value='<?= $ID ?>'>
<input type="hidden" name="selected" value="Да">

<div class="teams">
<?php foreach (array('seo'=>'Команда этого проекта SEO', 'site'=>'Команда проекта САЙТ') as $key=>$title) : ?>
<div>
<h2><?= $title ?></h2>
<?php foreach($teams[$key] as $user) : ?>
<?= $user['name'] ?><br>
<?php endforeach ?>
</div>
<?php endforeach ?>
<div>
<div>
<a href="#" class="show-inst">*</a>
<div>
"В первую очередь задачи назначаются сотрудникам в офисе. Если все в офисе загружены, то подключаем исполнителей на фри-лансе. 
В случае если нужно подключить фри-ланс исполнителя, то данную задачу мы адресуем не конкретному исполнителю, 
а сразу всем, кто по своей специализации может выполнить данную задачу. 
Данные специалисты ознакомятся с задачей и те, кто сможет в ближайшее время к ней приступить, напишут свои сроки в подтаблице к данной задаче "Оценка сроков".
Уведомления о факте ответов фри-ланс исполнителей будут отправлены на почту. 
Адресуем задачу тому, кто быстрее всех готов приступить к выполнению и называет самые короткие сроки. 
Или тому, кто первый ответил и готов начать в ближайшее время. Как только исполнитель понятен, в ДД "Кто делает задачу" снимаем галки выполнения задачи
с других, кто получил задачу на оценку сроков". Например, поставили трем исполнителям задачу, отписался 1, с двух других галки снимаем, чтобы задача закрепилась
за первым. "
</div>
</div>
<div>
<a class="show-memo" href="#">*</a>
<div>
<em>После выбора ответственного необходимо зайти в задачу и проверить, чтобы в поле "ИСПОЛНИТЕЛЬ" отображалась фамилия выбранного специалиста. </em>
</div>
</div>
</div>
</div>

<div class="owner">
<h2 >Ответственный по данной задаче:</h2>

<table>
<thead>
<th colspan="2">Исполнитель</th>
<th>Когда освободится?</th>
<th>Всего задач <br> в работе</th>
<th>Какие именно?</th>
<th>Кто делает?</th>
<th>Подать запрос на выполнение</th>
</thead>

<?php   foreach ($users as $user) : ?>
<tr>
<?php extract($user) ?>
<td><?= $name ?></td>
<td>
<?= $post ?><br>
<span class="post-comment"><?= $postComment ?></span>
</td>
<?php $doneTime=getUserDoneTime($tasks) ?>
<td><?= ($doneTime->isPastOrNow()) ? 'Свободен' : $doneTime ?></td>
<td><?= count($tasks) ?></td>
<td>
<?php if ($dept == $directors) : ?>

<?php elseif(!$tasks) : ?>
-
<?php else : ?>
<a class="show-tasks-list" href="#">*</a>

<ol>
<?php foreach ($tasks as $task) : ?>
<?php extract($task) ?>
<li><a href="/view_line2.php?table=47&filter=53&edit_mode=on&line=<?= $taskId ?>" target="blank"><?= "$domain - $shortDesc" ?></a></li>
<?php endforeach ?>
</ol>
<?php endif ?>
</td>
<td>
<?php if ($status != "Задачи не ставим") : ?>
<input type="radio" name="owner" value="<?= $id ?>">
<?php else : ?>
Задачи не ставим<br>
<a class="show-details" href="#">*</a>

<ul>
<li>Причина: <?= $reason ?></li>
<li>До: <?= $toWhat->date() ?></li>
<li>Тек. задачи: <?= $techTasksAct ?></li>
</ul>
<?php endif ?>
</td>
<td>
<?php if ($dept == "Фри-ланс отдел"): ?>
<input type="checkbox" name="fl[]" value="<?=$id?>">
<?php else :?>
-
<?php endif?>
</td>
</tr>
<?php endforeach ?>
</table>
</div>

<hr>
<input type="submit" class="submit-button" value="Сохранить">
</form>
<a href="/view_line2.php?table=47&filter=53&line=<?= $ID ?>">Назад к задаче</a>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript">
"use strict";

var DisplayToggler=new function () {
var constructor=function (data) {
for (var key in data) {
this[key]=data[key];
}

this.sources.click(this.toggle.bind(this));
this.hide();
}

var p=constructor.prototype;

p.show=function (source) {
source=source || this.sources;
var target=this.getTarget(source);
target.css('display', '');
this.onShow(source, target);
}

p.hide=function (source) {
source=source || this.sources;
var target=this.getTarget(source);
target.css('display', 'none');
this.onHide(source, target);
}

p.toggle=function (event) {
event.preventDefault();

var source=$(event.target);
var target=this.getTarget(source);
((target.css('display') == 'none') ? this.show : this.hide).call(this, source, target);
}

p.onShow= function (source) {
source.html('Скрыть');
}

p.onHide=function (source) {
source.html('Показать');
}

p.getTarget=function (source) {
return source.siblings('div, p, ul, ol');
}

return constructor;
}

new DisplayToggler({
sources: $('.show-tasks-list')
});

new DisplayToggler({
sources: $('.show-details'),

onHide: function(link) {
link.html('Подробнее');
},

});

var needOwnerClass='need-owner';
var radioButtons=$('[type=radio]');
var submitButton=$('.submit-button').addClass(needOwnerClass);

radioButtons.click(function (event) {
submitButton.removeClass(needOwnerClass);
});

submitButton.click(function (event) {
if (submitButton.hasClass(needOwnerClass)) {
event.preventDefault();
alert('Исполнитель не выбран');
}

});

new DisplayToggler({
sources: $('.show-memo'),

onHide: function(link) {
link.html('Показать памятку');
},

onShow: function(link) {
link.html('Скрыть памятку');
},

});

new DisplayToggler({
sources: $(".show-inst"),

onHide: function (l) {
l.html("       Инструкция для<br>распределяющего задачи");
},
});
</script>
</body>
</html>