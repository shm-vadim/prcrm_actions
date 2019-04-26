<?php

abstract class UserRepository
{
    public static function findTeamsByTaskData(array $line): array
    {
        $teamsRow = DB::row(
            'select 
c.f6181 as seo, 
c.f6171 as site 
from {table.company} c
where c.id in ({companyIdList})',
            [
                'companyIdList' => [$line['По компании']['raw'], $line['Домен текущего']['raw'], $line['Домен старого']['raw']],
            ]);

        if (null !== $teamsRow) {
            $site = self::getUsersByList($teamsRow['site']);
            $seo = self::getUsersByList($teamsRow['seo']);
        } else {
            $site = $seo = [];
        }

        return ['site' => $site, 'seo' => $seo];
    }

    public static function findByDepartments(array $departments): array
    {
        $sql = 'select s.id from {table.employee} s
join {table.user} u on(s.f483 = u.id)
WHERE u.arc = 0 and s.f5841 regexp {departments}';

        $userIdList = array_map(function (array $row): int {
            return $row['id'];
        }, DB::query($sql, [
            'departments' => new RegularExpressionCollectionParameter($departments),
        ]));

        return self::getEmployeesByIdList($userIdList);
    }

    private static function getUsersByIdList(array $userIdList): array
    {
        $employeeIdList = DB::column(
            'select e.id from {table.employee} e 
join {table.user} u on(e.f483 = u.id)
where u.id in({userIdList})',
            [
                'userIdList' => $userIdList,
            ]
        );

        return self::getEmployeesByIdList($employeeIdList);
    }

    private static function getUsersByList(string $idListString): array
    {
        $processedIdListString = preg_replace(
            ['#^-#', '#-$#', '#-#'],
            ['', '', "\r\n"],
            $idListString
        );

        return self::getUsersByIdList(explode("\r\n", $processedIdListString));
    }

    private static function getEmployeesByIdList(array $employeeIdList): array
    {
        $sql = 'select
s.id,
u.fio as `name`,
s.f484 as post,
s.f18340 as postComment,
s.f5841 as dept,
s.f6151 as status,
s.f18070 as reason,
s.f5861 as toWhat,
s.f18080 as techTasksAct
from {table.employee} s
join {table.user} u on(s.f483 = u.id)
join {table.group} g on(u.group_id = g.id)
where s.status = 0 and s.id in ({userIdList})
order by u.fio asc';

        $users = [];

        foreach (DB::query($sql, ['userIdList' => $employeeIdList]) as $user) {
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

        UserSorter::sortUsersByName($users);

        return $users;
    }
}
