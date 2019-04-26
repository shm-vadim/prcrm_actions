<?php

abstract class UserSorter
{
    /**
     * @return void
     */
    public static function sortUsersByName(array &$users)
    {
        usort($users, function (array $user1, array $user2): int {
            return mb_strtolower($user1['fio']) < mb_strtolower($user2['fio']) ? 0 : -1;
        });
    }
}
