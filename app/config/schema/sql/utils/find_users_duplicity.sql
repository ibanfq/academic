SELECT DISTINCT a.username as username FROM users a
INNER JOIN users b ON a.username = b.username AND b.id > a.id
