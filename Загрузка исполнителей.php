function stop($var) {
die(var_dump($var));
}

if (isset($_GET["owner"]) or isset($_GET["seo"]) or isset($_GET["site"]) ){
if ($owner=$_GET['owner']) $line['Исполнитель по задаче'] = implode("\r\n", $owner);
if ($site=$_GET["site"]) $line['Команда проекта САЙТ']=implode("\r\n",$site);
if ($seo=$_GET["seo"]) $line['Команда проекта SEO'] = implode("\r\n",$seo);
  $line['Назначен ли ответственный?'] = form_input($_GET['selected']);

  echo "<script>
window.opener.location.reload(true);
window.close();
</script>";
}

function getUserDoneTime($tasks) {
$min=60;
$hour=60*$min;
$day=24*$hour;
$toUtc=-3*$hour;
$start=10*$hour+$toUtc;
$finish=18*$hour+30*$min+$toUtc;
$taskDur=0;

foreach ($tasks as $task) {
if (dt::isRealDbTime($task['finish'])) continue;
$tasksDur+=$task['taskDur'];

$taskStart=$task['start'];
if (dt::isRealDbTime($taskStart)) {
$startTime=dt::createFromDb($taskStart)->getTimestamp();
$workDay=getDay($startTime);
$workTime=0;

while ($workDay < getDay()) {
$workDay+=$day;
if (isDayOff($workDay)) continue;
$workTime+=$finish-$start;
}

$workTime-=$startTime-(getDay($startTime)+$start);
if (time>getDay()+$start) {
$finTime=(time()<getDay()+$finish) ? time() : $finish;
$workTime+=$finTime-(getDay()+$start);
}
$tasksDur-=$workTime;
}
}

$doneTime=time()+$tasksDur*$hour;
$doneDay=getDay();
if ($doneTime > time()) {
while($doneTime > $doneDay+$finish or $doneTime < $doneDay + $start) {
$doneDay+=$day;
$doneTime+=$day;
if (isDayOff($doneDay)) continue;
$doneTime-=$finish-$start;
}
}

return dt::createFromTimestamp($doneTime);
}

function isDayOff($time) {
if (date('N', $time) >= 6) return true;
}

function getUserTasks($userId) {
extract(t());
return dbQuery("select $t.id as taskId, $t.f5811 as task, $t.f499 as taskDur, $t.f18060 as start, $t.f504 as finish,  $c.f435 as company from  $t 
join $c on($t.f1067 = $c.id)
where  $t.f492 = $userId");
}

function getDay($time=null) {
$day=24*60*60;
if ($time === null) $time=time();
return $day*((int) ($time/$day));
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
$idsStr=implode(', ', $ids);
$sql="select
$u.id,
$u.fio as `name`,
$g.name as `group`,
$s.f484 as post,
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
$user['doneTime']=getUserDoneTime($tasks);
$tw=$user['toWhat'];
$user['toWhat']=(dt::isRealDbTime($tw)) ? dt::createFromDb($tw) : '';
$users[$userId]=$user;
}

return sortUsersByPosts($users);
}

function sortUsersByPosts($arr) {
$groups=array();

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

$teams=getTeams($line['По компании']['raw']);
$directors='Руководитель';
$users=array_merge(getUsersByDepts(array(
'ТЕХ. ОТДЕЛ',
$directors,
))
);//
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Кто делает задачу</title>
  <style>
* {
margin: 0;
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
margin-left: 40px;
}

.owner {
   margin-left: 80px;
   }
   
.teams {
overflow: auto;
margin-left: 40px;
}

[type=submit] {
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
<input type="checkbox" name="<?= $key; ?>[]" value="<?= $user['id']; ?>">
<?= $user['name']; ?><br>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
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
</thead>

<?php   foreach ($users as $user) : ?>
<tr>
<?php extract($user); ?>
  <td><?= $name; ?></td>
   <td><?= $post; ?></td>
<td><?= ($doneTime->isPastOrNow()) ? 'Свободен' : $doneTime; ?></td>
<td><?= count($tasks); ?></td>
<td>
<?php if (!$tasks or $dept === $directors) : ?>
-
<?php else : ?>
<a class="show-tasks-list" href="#">*</a>

<ol>
<?php foreach ($tasks as $task) : ?>
<?php extract($task); ?>
<li><a href="/view_line2.php?table=47&filter=53&edit_mode=on&line=<?= $taskId; ?>" target="blank"><?= "$company - $task"; ?></a></li>
<?php endforeach; ?>
</ol>
<?php endif; ?>
</td>
<td>
<?php if ('Задачи не ставим' !== $status) : ?>
<input type="radio" name="owner" value="<?= $id; ?>">
<?php else : ?>
Задачи не ставим<br>
<a class="show-details" href="#">*</a>

<ul>
<li>Причина: <?= $reason; ?></li>
<li>До: <?= $toWhat->date(); ?></li>
<li>Тех. задачи: <?= $techTasksAct; ?></li>
</ul>
<?php endif; ?>
</td>
   </tr>
<?php endforeach; ?>
</table>
</div>

<hr>
<input type="submit" value="Сохранить">
</form>

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
</script>
</body>
</html>