/* Header */
NameEng: FireTrot.Counter
NameRus: Счетчик посетителей
DateStart: 2011 Jan 07

/* Task */
- Как должен работать?
Быстро.

- Для какой посещаемости должен подходить?
Высокой. Поэтому применяется БД, а не файловая система для хранения данных.

- Какие данные должен сохранять?
Все данные. Но основные такие:
* date_visit - дата посещения
* remote_addr - IP-адрес клиента (REMOTE_ADDR)
* remote_port - порт клиента (REMOTE_PORT)
* host - хост (HTTP_HOST)
* page_url - страница ресурса (REQUEST_URI)
* page_referer - страница, с которой перешли (HTTP_REFERER)
* user_agent - идентификатор программы-клиента (HTTP_USER_AGENT)

А также дополнительные:
* request_method
* query_string
* request_time
* http_accept
* http_accept_charset
* http_accept_encoding
* http_accept_language
* http_connection
* http_cookie
* http_keep_alive
* и другие, которые начинаютя с HTTP_

Расширеная статистика - простая таблица с данными.
IP - HostsCount - Country - OS - Browser

- Какие фреймворки использованы?
Для работы с БД использован ADOdb фреймворк (http://adodb.sourceforge.net/).
* В итоге отзалася от этой приблуды. Непонятные глюки при создании таблиц в SQLite.
Все отлично работает под PDO.

/* Technical realization */
Когда запрашивается веб-страница, происходит запись в БД данных о посетителе в таблицу tData.
После этого может выполнятся (если прошло определенное время с момента последнего обсчета) выполняется пересчет статистики с добавлением записей в таблицы tStat*.

В админ-панеле можна посмотреть графики статистики.
