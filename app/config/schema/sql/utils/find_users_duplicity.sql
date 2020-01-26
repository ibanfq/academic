SELECT DISTINCT a.username as username FROM users a
INNER JOIN users b ON a.username = b.username AND b.id > a.id

# TO FIX IT:
#
# update users_institutions set user_id = 1 where user_id = 2;
# 
# update subjects set coordinator_id = 1 where coordinator_id = 2;
# update subjects set practice_responsible_id = 1 where practice_responsible_id = 2;
# 
# update subjects_users set user_id = 1 where user_id = 2;
# 
# update registrations set student_id = 1 where student_id = 2;
# 
# update group_requests set student_id = 1 where student_id = 2;
# update group_requests set student_2_id = 1 where student_2_id = 2;
# 
# update events set teacher_id = 1 where teacher_id = 2;
# update events set owner_id = 1 where owner_id = 2;
# update events set teacher_2_id = 1 where teacher_2_id = 2;
# 
# update attendance_registers set teacher_id = 1 where teacher_id = 2;
# update attendance_registers set teacher_2_id = 1 where teacher_2_id = 2;
# 
# update users_attendance_register set user_id = 1 where user_id = 2;
# 
# update bookings set user_id = 1 where user_id = 2;
# 
# update users_booking set user_id = 1 where user_id = 2;
#
# ....
# 
# delete from users where id = 2;
#