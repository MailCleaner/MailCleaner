-- See #1024 for details

create view dupa as 
select count(username) as n, username 
from administrator 
group by username 
having n > 1;

create view keepa as 
select max(id) as id, a1.username 
from administrator as a1 
join dupa on a1.username = dupa.username 
group by a1.username;

delete a.* 
from administrator as a 
join dupa on a.username = dupa.username 
where a.id not in (select id from keepa);

alter table administrator add UNIQUE key uniq_username (username); 

drop view keepa;
drop view dupa;
