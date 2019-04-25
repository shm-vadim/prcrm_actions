<?php

$users = UserRepository::findByDepartments([
    'Руководитель',
    'Отдел продаж',
    'Отдел продаж (фри-ланс)',
    'Бух.отдел',
]);
