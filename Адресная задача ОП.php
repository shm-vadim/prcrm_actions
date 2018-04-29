if (isset($_GET["owner"])) {
if ($owner=$_GET['owner']) $line['Исполнитель по задаче'] = $owner;
  $line['Назначен ли ответственный?'] = form_input($_GET['selected']);

  echo "<script>
window.opener.location.reload(true);
window.close();
</script>";
}

function stop($var) {
die(var_dump($var));
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

return sortUsersByDepts(getUsersByIds($ids), $deptsArr);
}

function getUsersByIds($ids) {
extract(t());
if (!$idsStr=implode(', ', $ids)) $idsStr='null';

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

return $users;
}

function sortUsersByDepts($arr, $depts) {
$groups=array_flip($depts);

foreach ($arr as $user) {
$gn=$user['dept'];
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

$users=getUsersByDepts(array(
'Руководитель',
'Отдел продаж',
'Бух.отдел',
));//
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Адресная задача (Отдел продаж)</title>
</head>
<body>
<form method='get' style="margin:0; margin-bottom:-20px">
<input type='hidden' name='id' value='<?= $button_id ?>'>
<input type='hidden' name='line_id' value='<?= $ID ?>'>
<input type="hidden" name="selected" value="Да">

<h2 >Ответственный по данной задаче:</h2>
<table>
<?php   foreach ($users as $user) : ?>
<?php extract($user) ?>
<tr>
   <td><input type='checkbox' name='owner[]' value='<?= $id ?>'></td>
   <td><?= $name ?></td>
   <td><?= $post ?></td>
   </tr>
<?php endforeach ?>
</table>

<br><br>
<input type='submit' value='Сохранить'>
</form>
</body>
</html>