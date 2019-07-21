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
                        <?php if (isFreelanser($user)) : ?>
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