<?php

namespace App\Http\Controllers;

use App\Models\CompleteLab;
use App\Models\CourseTheme;
use App\Models\Discipline;
use App\Models\DisciplineResource;
use App\Models\ExamVariant;
use App\Models\Group;
use App\Models\JWT;
use App\Models\Lab;
use App\Models\LabConfig;
use App\Models\Notification;
use App\Models\Resource;
use App\Models\Teacher;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

use function Safe\date;

class AdminController extends Controller
{
    //? //////////////////////////////////////////////////////
    //? /////////////                           //////////////
    //? /////////////        ПРИГЛАШЕНИЯ        //////////////
    //? /////////////                           //////////////
    //? //////////////////////////////////////////////////////

        /**
         * * ГОТОВО, Создает приглашения
         */
        public function create_invitations(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'invitationsCount' => 'required|integer|min:1|max:50',
                'role' => 'required|string|max:10',
                'group' => 'required_if:role,student|string|min:8|max:12|nullable',
            ]);

            if ($validator->fails()) {
                return self::error('VALIDATION_ERROR', json_encode($validator->errors()->all()));
            }

            $role = '';
            $invite_length = 0;
            switch ($request->role) {
                case 'student':
                    $role = 91;
                    $invite_length = 10;
                    if ($request->invitationsCount > 50)
                        return self::error('VALIDATION_ERROR', "Можно создавать только по 50 приглашений для студентов за раз");
                    if ($request->group !== 'Без группы') {
                        if (Group::find($request->group) === null) {
                            return self::error('GROUP_UNDEFINED', "Группа не найдена, для создания перейдите в раздел групп");
                        }
                    }
                    break;
                case 'teacher':
                    $role = 92;
                    $invite_length = 20;
                    if ($request->invitationsCount > 20)
                        return self::error('VALIDATION_ERROR', "Можно создавать только по 20 приглашений для преподавателей за раз");
                    break;

                case 'admin':
                    $role = 93;
                    $invite_length = 40;
                    if ($request->invitationsCount > 5)
                        return self::error('VALIDATION_ERROR', "Можно создавать только по 5 приглашений для администраторов за раз");
                    break;

                default:
                    return self::error("ROLE_UNDEFINED", 'Указанная роль отсутствует в системе, проверьте ввод');
                    break;
            }

            $invites = [];
            $out = [];
            for ($i = 0; $i < $request->invitationsCount; $i++) {
                $temp_record = [];
                do {
                    $login = Str::upper(Str::random($invite_length));
                } while (User::where('login', $login)->first() !== null);
                $temp_record['login'] = $login;
                $temp_record['role'] = $role;
                if ($role === 91 && $request->group !== 'Без группы') {
                    $temp_record['id_group'] = $request->group;
                }
                $temp_record['created_at'] = date('Y-m-d H:i:s');
                $out[] = $temp_record;
                $temp_record['password'] = '';
                $invites[] = $temp_record;
            }

            if (!User::insert($invites))
                return self::error("SOMTHING_WRONG", "Ошибка при создании приглашений");

            return self::success("OK", $out);
        }

        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function all_disciplines(Request $request)
        {
            $disciplines = Discipline::all();
            foreach ($disciplines as $discipline) {
                $temp = [];
                foreach ($discipline->teachers as $teacher) {
                    $temp[] = $teacher->names();
                }
                $discipline->teachers_data = $temp;
                unset($discipline->teachers);
                if ($discipline->id_creator!==null){
                    $discipline->creator_data = User::find($discipline->id_creator)->names();
                }
            }
            return self::success("OK", $disciplines);

        }

        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function create_discipline(Request $request)
        {
            $discipline = new Discipline();
            $discipline->id_creator = $request->jwt_user_id;
            $discipline->description = $request->description;
            $discipline->save();
            return self::success("OK");
        }

        /**
         * * ГОТОВО, Удаляет одно приглашение
         */
        public function delete_invitation(Request $request)
        {
            if ($request->login) {
                if (!User::whereLogin($request->login)->where('role', '>', 90)->delete()) {
                    return self::error("DELETE_FAILED");
                } else {
                    return self::success("DELETE_SUCCESS");
                }
            }
        }

        /**
         * * ГОТОВО, Удаляет множество приглашений
         */
        public function delete_invitations(Request $request)
        {
            foreach ($request->deleteLogins as $login) {
                if (!User::whereLogin($login)->where('role', '>', 90)->first()) {
                    return self::error("DELETE_FAILED");
                }
            }
            foreach ($request->deleteLogins as $login) {
                if (!User::whereLogin($login)->where('role', '>', 90)->delete()) {
                    return self::error("DELETE_FAILED");
                }
            }
            return self::success("DELETE_SUCCESS");
        }

    //? //////////////////////////////////////////////////////
    //? /////////////                           //////////////
    //? /////////////       ПОЛЬЗОВАТЕЛИ        //////////////
    //? /////////////                           //////////////
    //? //////////////////////////////////////////////////////

        /**
         * * ГОТОВО
         * список пользователей по вкладкам
         */
        public function get_users(Request $request)
        {
            $return = [];
            switch ($request->tab) {
                case 'students':
                    $return = User::whereRole(1)->where('id_group', '!=', null)->orderBy('s_name');
                    break;
                case 'studentsNoGroup':
                    $return = User::whereRole(1)->where('id_group', '=', null)->orderBy('s_name');
                    break;
                case 'teachers':
                    $return = User::whereRole(2)->orderBy('s_name');
                    break;
                case 'admins':
                    $return = User::whereRole(3)->orderBy('s_name');
                    break;
                case 'invitations':
                    $return = User::where('role', '>', 90);
                    break;

                default:
                    return self::error("NO_SUCH_TAB");
                    break;
            }

            return self::success("OK", $return->get());
        }

        /**
         * * ГОТОВО
         * список пользователей по вкладкам
         */
        public function delete_user(Request $request)
        {
            $user = User::find($request->jwt_user_id);
            if(!$user || ! Hash::check($request->password, $user->password)){
                return self::error('LOGIN_ERROR','Неправильный пароль');
            }

            $deleting_user = User::whereLogin($request->login)->first();
            $id = $deleting_user->id_user;

            if ($request->jwt_user_id === $id){
                return self::error("DELETE_ERROR","Самоуничтожение запрещено");
            }

            JWT::where('id_user',$id)->delete();
            Notification::where('id_user',$id)->delete();

            if ($deleting_user->role===1){
                // Удаляем студента, значит надо очистить все связанные таблицы
                CourseTheme::where('id_student',$id)->delete();
                ExamVariant::where('id_student',$id)->delete();
                CompleteLab::where('id_student',$id)->delete();
                $deleting_user->delete();
            }else{
                // Удаляем препода или админа
                Teacher::where('id_user',$id)->delete();
                $deleting_user->delete();
            }

            return self::success("OK");
        }

    //! //////////////////////////////////////////////////////
    //! /////////////                           //////////////
    //! /////////////      НЕ ЗАДЕЙСТВОВАНО     //////////////
    //! /////////////                           //////////////
    //! //////////////////////////////////////////////////////

        /**
         * Выбирает все лабы одной дисциплины
         * @var int:id_discipline - номер дисциплины
         */
        public function labs_disciplines(Request $request)
        {
            $user = DB::table('disciplines')
                ->join('labs', 'disciplines.id_discipline', '=', 'labs.id_discipline')
                ->where('disciplines.id_discipline', '=', $request->id_discipline)
                ->select(DB::raw('disciplines.description, labs.description, file, comment'))
                ->get();

            return self::success("OK", $user);
        }

        /**
         *  группы-семестр. Смотрим какие группы по каким семестрам.
         * Я хз, нужна ли эта функция будет. Передаем номер дисциплины() И все.
         * @var id_discipline
         * @var
         */
        public function groups_semester(Request $request)
        {
            $user = DB::table('students')
                ->join('disciplines', 'students.id_discipline', '=', 'disciplines.id_discipline')
                ->where('students.id_discipline', '=', $request->id_discipline)
                ->select(DB::raw('id_group, edu_year, semester'))
                ->get();
            return self::success("OK", $user);
        }

        /**
         *
         */
        public function get_current_semester(Request $request)
        {
            $admission_year = User::find($request->jwt_user_id)->group->admission_year;

            $current_year = date('Y');

            if (intval(date('n')) < 8) {
                // Значит второй семестр, а год надо указать предыдущий
                $current_semester = 2;
                $current_year--;
            } else {
                $current_semester = 1;
            }

            $semester = ($current_year - $admission_year) * 2 + $current_semester;

            return self::success("OK", ['semester' => $semester]);
        }

        /**
         *  группы - список студентов в группе.
         * @var id_group - номер группы
         * @var
         */
        public function groups_students(Request $request)
        {
            $user = DB::table('students')
                ->join('disciplines', 'students.id_discipline', '=', 'disciplines.id_discipline')
                ->where('students.id_discipline', '=', $request->id_discipline)
                ->select(DB::raw('id_group, edu_year, semester'))
                ->get();
            return self::success("OK", $user);
        }

        /**
         * лабы в группе по дисциплине. Передаем номер группы() и номер дисциплины() соответственно чтобы это увидеть.
         * @var id_group
         * @var id_discipline
         */
        public function lab_in_group_old(Request $request)
        {
            $user = DB::table('lab_configs')
                ->join('labs', 'lab_configs.id_lab', '=', 'labs.id_lab')
                ->where('id_group', '=', $request->id_group)
                ->where('id_discipline', '=', $request->id_discipline)
                ->select(DB::raw('lab_configs.id_lab, deadline, allowed_after'))
                ->get();
            return self::success("OK", $user);
        }


        /**
         * список групп. Тут просто выводит, ничего не передаем.
         */
        public function groups()
        {
            $user = DB::table('groups')
                ->select('id_group', 'admission_year')
                ->get();
            return self::success("OK", $user);
        }

    //! //////////////////////////////////////////////////////

}
