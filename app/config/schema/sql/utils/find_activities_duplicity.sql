SELECT DISTINCT institutions.name as institution, year(courses.initial_date) as course, subjects.name as subject, a.name as activity FROM activities a
INNER JOIN activities b ON a.subject_id = b.subject_id AND a.name = b.name AND b.id > a.id
LEFT JOIN subjects ON subjects.id = a.subject_id
LEFT JOIN courses ON courses.id = subjects.course_id
LEFT JOIN institutions ON institutions.id = courses.institution_id
