# Вариант 1
![Схема 1](variant1.jpg)

## Описание
* Пулл воркеров имеет конфигурацию, которая описывает **минимальное** количество задач *указанного приоритета*, которое нужно поддерживать
* При добавлении пользователем торрента на загрузку, в конец очереди встает соотвествующая задача, с указанием ее приоритета
* При освобождении очередного воркера, он проверяет, сколько задача в каком приоритете в работе (начиная с высокоприоритетных)
  * Если задач высокого приоритета в работе меньше, чем указана в конфигурации пулла, то воркер берет в работу следующую задача **высокого приоритета**
  * Иначе, проверяет следующий по порядку приоритет
  * ...
  * Если _приоритеная загрузка_ пулла соответствует его конфигурации, то воркер берет следующуюю по порядку задачу
* Воркер причисляет себя к пуллу, согласно приоритету взятой задачи

## Плюсы
* Простая реализация

## Минусы
* Теоретически, весь пул воркеров может быть заполнен долгими задачами, что заблокирует выполнение поступающих высокоприоритетных задач. _Частичное решение_: развить конфиг воркеров таким образом, что бы часть воркеров брала **только** высокоприоритетные задачи, и ждала если их сейчас нет.


# Вариант 2
![Схема 2](variant2.jpg)

## Описание
* Все задачи оцениваются по группе параметров, что выражается в **"ценность"** (score) каждой задачи, которая меняется с течением времени и прогрессом задачи:
  * Чем выше приоритет (тариф пользователя) тем **выше** _score_
  * Чем доступнее торрент (выше скорость, больше источников) тем **выше** _score_
  * Чем больше времени прошло с заказа торрента, тем **выше** _score_
  * Чем больше оставшийся объем загрузки, тем **ниже** _score_
  * Если задача уже находится в работе, то это увеличивает ее _score_
* Задачи от пользователей попадают в очередь, в которой ранжируются по _score_
* _score_ задач переодически пересчитывается, очередь пересортировывается
* В работе всегда находятся задачи с наивысшем _score_ в очереди - **рабочий пул**
* Если после пересчета очереди, задача выпадает из **рабочего пула**, то воркер выгружает ее в хранилище в _незаконченном виде_ и берет в работу задачу, которая попала в **рабочий пул** вместо выбывшей, при необходимости нужно выгрузить из хранилища прогресс задачи, если она уже бывала в работе

## Плюсы
* Обеспечивает параметрический подход к определению приоритетов задач
* Обеспечивает ротацию слишком медленных задач, что бы они не занимали весь рабочий пул

## Минусы
* Необходим периодический пересчет очереди
* Усложнение воркеров, что бы они могли отдавать задачи и заменять их новыми
* Необходимость хранения не завершенных загрузок в общем хранилище для передачи между воркерами
