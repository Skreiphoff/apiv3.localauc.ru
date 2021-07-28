<?php

namespace App\Http\Controllers;

use App\Models\BooleanSwitchers;
use App\Models\CompleteLab;
use App\Models\CourseTheme;
use App\Models\Discipline;
use App\Models\DisciplineResource;
use App\Models\Group;
use App\Models\Lab;
use App\Models\LabConfig;
use App\Models\Notification;
use App\Models\Resource;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

use function Safe\date;

class TeacherController extends Controller
{
    //? //////////////////////////////////////////////////////
    //? /////////////                           //////////////
    //? /////////////          РЕСУРСЫ          //////////////
    //? /////////////                           //////////////
    //? //////////////////////////////////////////////////////

        // * ГОТОВО, загружает файл ресурса
        public function upload_resource(Request $request)
        {
            return FilesController::upload_resource($request);
        }


        /**
         * @var
         * @var
         */
        public function get_discipline_resources(Request $request)
        {
            $resources = Discipline::find($request->discipline)->resources;
            return self::success("OK", $resources);
        }


        /**
         * ! не задействовано
         * удалить ресурс. передаем id ресурса(id_resource) что нужно удалить и все.
         * @var
         * @var
         */
        public function delete_resources(Request $request)
        {
            $user = DB::table('resources')
                ->where('id_resource', '=', $request->id_resource)
                ->delete();
            return self::success("OK", $user);
        }

        /**
         * ! не задействовано
         * удалить ресурсы из дисциплины.  Передаем чтобы удалить id-шник дисциплины(id_discipline) и id-шник ресурса, который нужно убрать из дисциплины(id_resource)
         * @var
         * @var
         */
        public function delete_resource_from_discipline(Request $request)
        {
            $user = DB::table('discipline_resources')
                ->where('id_discipline', '=', $request->discipline)
                ->where('id_resource', '=', $request->resource)
                ->delete();
            return self::success("OK", $user);
        }

    //? //////////////////////////////////////////////////////
    //? /////////////                           //////////////
    //? /////////////         ДИСЦИПЛИНЫ        //////////////
    //? /////////////                           //////////////
    //? //////////////////////////////////////////////////////

        // * Получение данных дисциплины с проверкой по привязке к учителю
        public function discipline_data(Request $request)
        {
            $user = User::find($request->jwt_user_id);


            if($user->role===3){
                $discipline = Discipline::where('id_discipline', $request->discipline)
                ->first();
            }else{
                if (!$discipline = $user->teachers_disciplines->where('id_discipline', $request->discipline)
                    ->first()) {
                    return self::error("NO_SUCH_DISCIPLINE"); //For this user of course ;)
                };
            }

            $temp = [];
            foreach ($discipline->teachers->sortBy('s_name') as $teacher) {
                $names = $teacher->names();
                $names->self = $names->id_user===$request->jwt_user_id;
                $temp[] = $names;
            }
            $discipline->teachers_data = $temp;
            unset($discipline->teachers);

            foreach ($discipline->students as $students) {
                $students->group->students_count = count($students->group->students);
                unset($students->group->students);
            }

            $discipline->exam_forms_convert();

            return self::success("OK", $discipline);
        }


        /**
         * список моих дисциплин. в этом семестре
         * Сюда передаем id пользователя(препода)
         * @var jwt_user_id
         */
        public function current_disciplines(Request $request)
        {
            $current_edu_year = date('Y');

            if (intval(date('n')) < 8) {
                // Значит второй семестр, а год надо указать предыдущий
                $current_semester = 2;
                $current_edu_year--;
            } else {
                $current_semester = 1;
            }

            $disciplines = User::find($request->jwt_user_id)->teachers_disciplines;

            foreach ($disciplines as $discipline) {
                $discipline->students->where('edu_year', $current_edu_year)->where('semester', $current_semester);
            }

            return self::success("OK", $disciplines);
        }

        /**
         * список моих дисциплин. в этом семестре
         * Сюда передаем id пользователя(препода)
         * @var jwt_user_id
         */
        public function my_disciplines(Request $request)
        {
            $disciplines = User::find($request->jwt_user_id)->teachers_disciplines;
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
        public function create_theme(Request $request)
        {
            $discipline = new CourseTheme();
            $discipline->id_discipline = $request->discipline;
            $discipline->description = $request->description;
            $discipline->confirmed = 0;
            $discipline->save();
            return self::success("OK");
        }

        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function create_config(Request $request)
        {

            $discipline = new LabConfig();
            // LabConfig::insert([

            // ])
            $discipline->id_lab = $request->lab;
            $discipline->id_group = $request->group;
            $discipline->allowed_after = $request->allowed_after;
            $discipline->deadline = $request->deadline;
            $discipline->save();
            return self::success("OK");
        }
        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function get_config(Request $request)
        {
            $d = Discipline::where('id_discipline' , $request->discipline)->first();
            $labs = $d->labs;
            $configs = [];
            foreach ($labs as $lab) {
                $temp = $lab->lab_config->where('id_group',$request->group)->first();
                if ($temp !== null)
                $configs[] = [
                    'lab' => $lab,
                    'config' => $lab->lab_config->where('id_group',$request->group)->first(),
                ];
            }

            return self::success("OK", $configs);

        }
        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function delete_config(Request $request)
        {
            LabConfig::drop([
                'id_lab'=>$request->lab,
                'id_group'=>$request->group
            ]);
            return self::success("OK");
        }

        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function get_themes(Request $request)
        {
            $themes = CourseTheme::where('id_discipline' , $request->discipline)->get();
            foreach ($themes as $theme) {
                if ($theme->id_student !== null){
                    $theme->student;
                    if ($theme->student->photo!==null)
                    $theme->student->photo = asset(Storage::url($theme->student->photo));;
                }
            }
            return self::success("OK",$themes);
        }

        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function confirm_theme(Request $request)
        {
            $themes = CourseTheme::find($request->theme);
            $themes->confirmed = 1;
            $themes->save();
            return self::success("OK");
        }

        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function reset_theme(Request $request)
        {
            $themes = CourseTheme::find($request->theme);
            $themes->confirmed = 0;
            $themes->save();
            return self::success("OK");
        }

        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function decline_theme(Request $request)
        {
            $themes = CourseTheme::find($request->theme);
            $themes->confirmed = -1;
            $themes->save();
            return self::success("OK");
        }
        /**
         * * ГОТОВО
         * @var jwt_user_id
         */
        public function delete_theme(Request $request)
        {
            $themes = CourseTheme::find($request->theme);
            $themes->delete();
            return self::success("OK");
        }

        /**
         * список моих дисциплин. в этом семестре
         * Сюда передаем id пользователя(препода)
         * @var jwt_user_id
         */
        public function add_teacher_to_discipline(Request $request)
        {
            $teacher = User::find($request->teacher);
            if (!$teacher||($teacher->role!==2&&$teacher->role!==3)) {
                return self::error("TEACHER_NOT_EXIST","Данный пользователь не существует");
            }

            $discipline = Discipline::find($request->discipline);
            if (!$discipline) {
                return self::error("DISCIPLINE_NOT_EXIST","Данная дисплина не существует");
            }

            if (Teacher::where('id_discipline',$request->discipline)->where('id_user',$request->teacher)->first()!==null){
                return self::success("ALREADY_IN_DISCIPLINE");
            }

            $result = Teacher::insert([
                'id_discipline'=>$request->discipline,
                'id_user'=>$request->teacher
            ]);

            if (!$result){
                return self::error("SMTH_WRONG");
            }

            return self::success("OK",$teacher->names());
        }

        /**
         * список моих дисциплин. в этом семестре
         * Сюда передаем id пользователя(препода)
         * @var jwt_user_id
         */
        public function remove_teacher_from_discipline(Request $request)
        {
            $teacher = User::find($request->teacher);
            if (!$teacher||($teacher->role!==2&&$teacher->role!==3)) {
                return self::error("TEACHER_NOT_EXIST","Данный пользователь не существует");
            }

            if ($request->teacher === $request->jwt_user_id){
                return self::error("SELF_DESTRUCTION","Самоуничтожение запрещено!");
            }

            $discipline = Discipline::find($request->discipline);
            if (!$discipline) {
                return self::error("DISCIPLINE_NOT_EXIST","Данная дисплина не существует");
            }

            if (Teacher::where('id_discipline',$request->discipline)->where('id_user',$request->teacher)->first()===null){
                return self::success("ALREADY_REMOVED_FROM_DISCIPLINE");
            }

            $result = Teacher::where([
                'id_discipline'=>$request->discipline,
                'id_user'=>$request->teacher
            ])->delete();

            if (!$result){
                return self::error("SMTH_WRONG");
            }

            return self::success("OK");
        }

        /**
         * список моих дисциплин. в этом семестре
         * Сюда передаем id пользователя(препода)
         * @var jwt_user_id
         */
        public function add_group_to_discipline(Request $request)
        {
            $group = Group::find($request->group);
            if (!$group) {
                return self::error("GROUP_NOT_EXIST","Данная группа не существует");
            }

            $discipline = Discipline::find($request->discipline);
            if (!$discipline) {
                return self::error("DISCIPLINE_NOT_EXIST","Данная дисплина не существует");
            }

            if (Student::where('id_discipline',$request->discipline)->where('id_group',$request->group)->first()!==null){
                return self::success("ALREADY_IN_DISCIPLINE");
            }

            $insert_data = [
                'id_discipline'=>$request->discipline,
                'id_group'=>$request->group,
                'edu_year'=>$request->eduYear,
                'semester'=>$request->semester,
            ];


            if (!Student::insert($insert_data)){
                return self::error("SMTH_WRONG");
            }

            $group->students_count = count($group->students);
            unset($group->students);
            $insert_data['group'] = $group;

            return self::success("OK",$insert_data);
        }

        /**
         * список моих дисциплин. в этом семестре
         * Сюда передаем id пользователя(препода)
         * @var jwt_user_id
         */
        public function remove_group_from_discipline(Request $request)
        {
            $group = Group::find($request->group);
            if (!$group) {
                return self::error("GROUP_NOT_EXIST","Данная группа не существует");
            }

            $discipline = Discipline::find($request->discipline);
            if (!$discipline) {
                return self::error("DISCIPLINE_NOT_EXIST","Данная дисплина не существует");
            }

            if (Student::where('id_discipline',$request->discipline)->where('id_group',$request->group)->first()===null){
                return self::success("ALREADY_OUT_FROM_DISCIPLINE");
            }

            $result = Student::where([
                'id_discipline'=>$request->discipline,
                'id_group'=>$request->group,
            ])->delete();

            if (!$result){
                return self::error("SMTH_WRONG");
            }

            return self::success("OK");
        }

        /**
         * список моих дисциплин. в этом семестре
         * Сюда передаем id пользователя(препода)
         * @var jwt_user_id
         */
        public function set_discipline_exam_forms(Request $request)
        {
            $discipline = Discipline::find($request->discipline);
            if (!$discipline) {
                return self::error("DISCIPLINE_NOT_EXIST","Данная дисплина не существует");
            }

            $forms = [];
            if ($request->switchers['course'])    $forms[]='Курсовая работа';
            if ($request->switchers['exam'])      $forms[]='Экзамен';
            if ($request->switchers['credit'])    $forms[]='Зачет';
            if ($request->switchers['difCredit']) $forms[]='Диф. зачет';

            $discipline->exam_forms = BooleanSwitchers::convert_switchers($forms,config('switchers.exam_forms'));

            $discipline->save();
            return self::success("OK",$forms);
        }

    //? //////////////////////////////////////////////////////
    //? /////////////                           //////////////
    //? /////////////           ЛАБЫ            //////////////
    //? /////////////                           //////////////
    //? //////////////////////////////////////////////////////

        /**
         * * ГОТОВО.
         * * Получение списков для таблицы сводки по лабам
         */
        public function lab_in_group(Request $request)
        {
            $user = User::find($request->jwt_user_id);
            if (!$discipline = $user->teachers_disciplines->where('id_discipline', $request->discipline)
                ->first()) {
                return self::error("NO_SUCH_DISCIPLINE");
            };

            $group_students = User::whereRole(1)->where('id_group', $request->group)->get();
            $labs = Lab::where('id_discipline', $request->discipline)->where('id_form', 1)->get();
            foreach ($labs as $lab) {
                $config = $lab->lab_config->where('id_group', $request->group)
                    ->where('allowed_after', '<', 'CURRENT_TIMESTAMP')
                    ->first();
                if ($config) {
                    $lab->deadline = $config->deadline;
                }
                $lab->complete_labs;
            }

            if (!$labs->first()) {
                //Если нет первой то остальных и подавно
                return self::success("NO_LABS");
            }

            return self::success("OK", [
                'students' => $group_students,
                'labs' => $labs,
            ]);
        }

        /**
         * проставить оценку за лабу. тут передаем id лабы(), id студента(), id препода что проставит, то есть меня(id_teacher), оценку само собой(mark) и комментарий(comment) если надо.
         * @var id_lab
         * @var id_student
         */
        public function mark_for_lab(Request $request)
        {
            $user = DB::table('complete_labs')
                ->where('id_lab', $request->id_lab)
                ->where('id_student', $request->id_student)
                ->update([
                    'id_teacher' => $request->jwt_user_id,
                    'mark' => $request->mark,
                    'comments' => $request->comments,
                    'status' => '2'
                ]);

            $lab = Lab::find($request->id_lab);
            $teacher = User::find($request->jwt_user_id)->names();

            $text = 'Работа "'.$lab->description.
            '" по предмету "'.$lab->discipline->description.
            '" была проверена. Проверил: '.$teacher->s_name.' '.$teacher->f_name.' ';
            if ($teacher->fth_name!==null){
                $text.= $teacher->fth_name.'. ';
            }
            if ($request->comments!==null){
                $text.= " Комментарий: ".$request->comments;
            }
            $text.="Оценка: ".$request->mark;

            Notification::create([
                'id_user'=>$request->id_student,
                'text'=>$text,
            ]);
            return self::success("OK", $user);
        }

        /**
         * проставить оценку за лабу. тут передаем id лабы(), id студента(), id препода что проставит, то есть меня(id_teacher), оценку само собой(mark) и комментарий(comment) если надо.
         * @var id_lab
         * @var id_student
         */
        public function get_discipline_labs(Request $request)
        {
            $labs = Lab::where('id_discipline',$request->discipline)->where('id_form',1)->get();
            return self::success("OK", $labs);
        }

        public function upload_lab_file(Request $request)
        {
            return FilesController::upload_lab($request);
        }

        public function check_lab_data(Request $request)
        {
            $lab = Lab::find($request->lab);
            if (!$lab) return self::error("LAB_NOT_EXIST");

            $messages = [];

            $answers = CompleteLab::where('id_lab',$request->lab)->get();
            $configs = LabConfig::where('id_lab',$request->lab)->get();

            if ($answers!==null){
                $marked_answers = count(CompleteLab::where('id_lab',$request->lab)->where('status',2)->get());
                $messages[] = "На эту работу студенты уже загружали свои ответы, они будут удалены. ";
                $messages[] = "Ответов: ".count($answers).", из них ожидают оценки: ".(count($answers) - $marked_answers).".";
                $messages[] = " ";
            }
            if ($configs!==null){
                $messages[] = "Существуют настройки доступности и сроков для учебных групп. Если продолжить, они будут удалены. ";
                $messages[] = "Существует ".count($configs)." настроек.";
            }

            if (!empty($messages)){
                return self::success('DATA_EXISTS',$messages);
            }else{
                return self::success('DATA_NOT_EXISTS');
            }
        }

        public function create_lab(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'description' => 'required|string|min:1|max:70',
                'comment' => 'string|nullable',
                'file'=>'file'
            ]);

            if ($validator->fails()) {
                return self::error('VALIDATION_ERROR', json_encode($validator->errors()->all()));
            }

            if ($request->deadlineNeed==='true'||$request->deadlineNeed===true){
                $deadline = date('Y-m-d 23:59:59',strtotime($request->deadline));
                if (strtotime($deadline)<time()) return self::error('DATE_INVALID',"Недопустимая дата");
            }else{
                $deadline = null;
            }

            $data = [
                'id_discipline'=>$request->discipline,
                'description'=>$request->description,
                'comment'=>$request->comment,
                'id_form'=>1,
                'file'=>null,
            ];

            if (!Lab::create($data)){
                return self::error('SMTH_WRONG');
            }

            $lab = Lab::where($data)->first();
            $request->lab = $lab->id_lab;

            $result = FilesController::upload_lab($request,TRUE);
            if ($result!=="LAB_UPLOADED"){
                $lab->delete();
                return self::error($result,"Ошибка при загрузке файла");
            }

            if ($request->openLab==='true'||$request->openLab===true){
                $current_edu_year = date('Y');
                if (intval(date('n')) < 8) {
                    $current_semester = 2;
                    $current_edu_year--;
                } else {
                    $current_semester = 1;
                }

                $groups = Student::where([
                    'id_discipline'=>$request->discipline,
                    'edu_year'=>$current_edu_year,
                    'semester'=>$current_semester,
                ])->get();

                foreach ($groups as $group) {
                    LabConfig::insert([
                        'id_lab'=>$lab->id_lab,
                        'id_group'=>$group->id_group,
                        'allowed_after'=>date('Y-m-d 23:59:59'),
                        'deadline'=>$deadline,
                    ]);
                }
            }

            return self::success("LAB_CREATED",$lab);
        }

        public function delete_lab(Request $request)
        {
            $lab = Lab::find($request->lab);
            if (!$lab) return self::error("LAB_NOT_EXIST");
            LabConfig::where('id_lab',$request->lab)->delete();

            $answers = CompleteLab::where('id_lab',$request->lab)->get();
            foreach ($answers as $answer) {
                Storage::delete($answer->file);
            }
            CompleteLab::where('id_lab',$request->lab)->delete();
            $lab->delete();
            return self::success("LAB_DELETED");
        }


    //? //////////////////////////////////////////////////////
    //? /////////////                           //////////////
    //? /////////////          ГРУППЫ           //////////////
    //? /////////////                           //////////////
    //? //////////////////////////////////////////////////////

        /**
         * * ГОТОВО
         * Выводит список групп
         */
        public function groups()
        {
            $groups = Group::orderBy('admission_year','DESC')->orderBy('id_group','DESC')->get();
            foreach ($groups as $group) {
                $group->students_count = count($group->students);
                unset($group->students);
            }
            return self::success("OK", $groups);
        }

        /**
         * * ГОТОВО
         * Выводит список студентов в группе
         */
        public function get_group_students(Request $request)
        {
            $users = User::where('id_group',$request->group)->get();
            return self::success("OK", $users);
        }

        /**
         * удалить студента из группы. Просто как бы тут убираем запись о группе(ставим NULL) по id_group в users.
         * @var
         * @var
         */
        public function remove_student_from_group(Request $request)
        {
            $user = User::find($request->student);
            if (!$user) return self::error("USER_NOT_EXIST");
            if ($user->role===91) {
                $user->delete();
            }else{
                $user->id_group = NULL;
                $user->save();
            }
            return self::success("OK");
        }


        /**
         *  добавить группу(id_group) и ее год зачисления(admission_year). Тут очевидно - просто добавляем новую группу с этими параметрами.
         * @var
         * @var
         */
        public function create_group(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'invitationsCount' => 'required|integer|min:0|max:50',
                'group' => 'required|string|min:8|max:12|unique:groups,id_group',
                'admissionYear' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return self::error('VALIDATION_ERROR', json_encode($validator->errors()->all()));
            }

            $current_year = intval(date('Y'));
            if ($request->admissionYear>$current_year || $request->admissionYear<$current_year-6){
                return self::error("INVALID_YEAR",'Недопустимый год поступления');
            }

            if (!Group::insert([
                'id_group'=>$request->group,
                'admission_year'=>$request->admissionYear,
            ])){
                return self::error("SOMETHING_WRONG", "Ошибка при создании группы");
            }

            $invites = [];
            $out = [];
            for ($i = 0; $i < $request->invitationsCount; $i++) {
                $temp_record = [];
                do {
                    $login = Str::upper(Str::random(10));
                } while (User::where('login', $login)->first() !== null);
                $temp_record['login'] = $login;
                $temp_record['role'] = 91;
                $temp_record['id_group'] = $request->group;
                $temp_record['created_at'] = date('Y-m-d H:i:s');
                $out[] = $temp_record;
                $temp_record['password'] = '';
                $invites[] = $temp_record;
            }

            if (!User::insert($invites)) {
                Group::find($request->group)->delete();
                return self::error("SOMETHING_WRONG", "Ошибка при создании приглашений");
            }

            return self::success("OK", $out);

        }

        /**
         * удалить группу. Удаляем по idшнику группу(id_group)
         * @var
         * @var
         */
        public function delete_group(Request $request)
        {
            $group = Group::find($request->group);
            if (!$group){
                self::error("GROUP_NOT_EXIST","Данная группа не существует");
            }

            if (User::where('id_group',$request->group)->where('role','!=',91)->first()){
                // TODO: Переход к админу, проверка пароля
                return self::error("ACCESS_DENIED","Удалять группы со студентами может только администратор");
            }

            User::whereRole(91)->where('id_group',$request->group)->delete();

            if (!$group->delete()){
                return self::error("SOMETHING_WRONG","Ошибка при удалении ");
            }

            return self::success("OK");
        }

        public function add_student_to_group(Request $request)
        {
            $group = Group::find($request->group);
            if (!$group){
                return self::error("GROUP_NOT_EXIST","Данная группа не существует");
            }

            $user = User::find($request->student);
            if (!$user) return self::error("USER_NOT_EXIST","Студент не существует");
            if ($user->id_group!==null) return self::error("USER_ALREADY_HAS_GROUP","Студент уже находится в этой или другой группе");
            if ($user->role!==1) return self::error("USER_INVALID","Это не студент");
            $user->id_group = $request->group;
            $user->save();
            return self::success("OK",$user);
        }

        public function get_groups_without_such_discipline(Request $request)
        {
            $groups = Group::orderBy('admission_year','DESC')->orderBy('id_group','DESC')->get();
            $result = [];
            foreach ($groups as $group) {
                $group->students_count = count($group->students);
                unset($group->students);
                if (Student::where('id_discipline',$request->discipline)->where('id_group',$group->id_group)->first()===null){
                    $result[] = $group;
                }
            }
            return self::success("OK",$result);
        }

    //? //////////////////////////////////////////////////////
    //? /////////////                           //////////////
    //? /////////////       ПОЛЬЗОВАТЕЛИ        //////////////
    //? /////////////                           //////////////
    //? //////////////////////////////////////////////////////

        public function create_students(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'invitationsCount' => 'required|integer|min:1|max:50',
                'group' => 'required|string|min:8|max:12|exists:groups,id_group',
            ]);

            if ($validator->fails()) {
                return self::error('VALIDATION_ERROR', json_encode($validator->errors()->all()));
            }

            $invites = [];
            $out = [];
            for ($i = 0; $i < $request->invitationsCount; $i++) {
                do {
                    $login = Str::upper(Str::random(10));
                } while (User::where('login', $login)->first() !== null);
                $temp_record = [
                    'login' => $login,
                    'role' => 91,
                    'id_group' => $request->group,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $out[] = $temp_record;
                $temp_record['password'] = '';
                $invites[] = $temp_record;
            }

            if (!User::insert($invites))
                return self::error("SOMTHING_WRONG", "Ошибка при создании приглашений");

            return self::success("OK", $out);
        }

        public function get_students_without_group(Request $request)
        {
            $users = User::whereRole(1)->whereIdGroup(NULL)->get();
            return self::success("OK",$users);
        }

        public function get_teachers_without_such_discipline(Request $request)
        {
            $users = User::where('role',2)->orWhere('role',3)->get();
            $result = [];
            foreach ($users as $user) {
                if (Teacher::where('id_discipline',$request->discipline)->where('id_user',$user->id_user)->first()===null){
                    $result[] = $user->names();
                }
            }
            return self::success("OK",$result);
        }


    //! //////////////////////////////////////////////////////
    //! /////////////                           //////////////
    //! /////////////      НЕ ЗАДЕЙСТВОВАНО     //////////////
    //! /////////////                           //////////////
    //! //////////////////////////////////////////////////////


        // /**
        //  * ! Незадействовано
        //  * Выбирает все лабы одной дисциплины
        //  * @var int:id_discipline - номер дисциплины
        //  */
        // public function labs_disciplines(Request $request)
        // {
        //     $user = DB::table('disciplines')
        //         ->join('labs', 'disciplines.id_discipline', '=', 'labs.id_discipline')
        //         ->where('disciplines.id_discipline', '=', $request->id_discipline)
        //         ->select(DB::raw('disciplines.description, labs.description, file, comment'))
        //         ->get();

        //     return self::success("OK", $user);
        // }

        // /**
        //  * ! Незадействовано
        //  *  группы-семестр. Смотрим какие группы по каким семестрам.
        //  * Я хз, нужна ли эта функция будет. Передаем номер дисциплины() И все.
        //  * @var id_discipline
        //  * @var
        //  */
        // public function groups_semester(Request $request)
        // {
        //     $user = DB::table('students')
        //         ->join('disciplines', 'students.id_discipline', '=', 'disciplines.id_discipline')
        //         ->where('students.id_discipline', '=', $request->id_discipline)
        //         ->select(DB::raw('id_group, edu_year, semester'))
        //         ->get();
        //     return self::success("OK", $user);
        // }


        // /**
        //  *  группы - список студентов в группе.
        //  * @var id_group - номер группы
        //  * @var
        //  */
        // public function groups_students(Request $request)
        // {
        //     $user = DB::table('students')
        //         ->join('disciplines', 'students.id_discipline', '=', 'disciplines.id_discipline')
        //         ->where('students.id_discipline', '=', $request->id_discipline)
        //         ->select(DB::raw('id_group, edu_year, semester'))
        //         ->get();
        //     return self::success("OK", $user);
        // }




        // /**
        //  * добавить лабу или экзамен в группу
        //  * @var
        //  * @var
        //  */
        // public function insert_lab_in_group(Request $request)
        // {
        //     $user = DB::table('lab_configs')
        //         ->insert(['lab_configs.id_lab' => $request->id_lab, 'lab_configs.id_group' => $request->id_group, 'deadline' => $request->deadline, 'allowed_after' => $request->allowed_after]);
        //     return self::success("OK", $user);
        // }

        // /**
        //  *  добавить группу в дисциплину
        //  * @var
        //  * @var
        //  */
        // public function insert_group_in_discipline(Request $request)
        // {
        //     $user = DB::table('students')
        //         ->insert([
        //             'id_discipline' => $request->id_discipline,
        //             'id_group' => $request->id_group,
        //             'edu_year' => $request->edu_year,
        //             'semester' => $request->semester
        //         ]);
        //     return self::success("OK", $user);
        // }

        // /**
        //  *  добавить студента в группу. передаем idшник пользователя(студента)(jwt_user_id), и номер группы(id_group) куда зачислять.
        //  * @var
        //  * @var
        //  */
        // public function insert_student_in_group(Request $request)
        // {
        //     $user = DB::table('users')
        //         ->where('jwt_user_id', $request->jwt_user_id)
        //         ->update(['id_group' => $request->id_group]);
        //     return self::success("OK", $user);
        // }



        // /**
        //  * удалить лабы или экзамены в группе по дисциплине. Тут смотри, передаем номер группы(id_group), номер дисциплины(id_discipline), и id лабы или экзамена соответственно(id_lab)
        //  * @var
        //  * @var
        //  */
        // public function delete_lab_in_group(Request $request)
        // {
        //     $user = DB::table('lab_configs')
        //         ->join('labs', 'lab_configs.id_lab', '=', 'labs.id_lab')
        //         ->where('id_group', '=', $request->id_group)
        //         ->where('id_discipline', '=', $request->id_discipline)
        //         ->where('lab_configs.id_lab', '=', $request->id_lab)
        //         ->delete();
        //     return self::success("OK", $user);
        // }

        // /**
        //  *  удалить группу из семестра по дисциплине. В таблицу students передаем id_discipline(айдишник дисциплины) и группу(id_group).
        //  * @var
        //  * @var
        //  */
        // public function delete_groups_semester(Request $request)
        // {
        //     $user = DB::table('students')
        //         ->join('disciplines', 'students.id_discipline', '=', 'disciplines.id_discipline')
        //         ->where('students.id_discipline', '=', $request->id_discipline)
        //         ->where('students.id_group', '=', $request->id_group)
        //         ->delete();
        //     return self::success("OK", $user);
        // }



        // /**
        //  * список экзаменов в группе по дисциплине description - название, allowed_after - дата начала экзамена(когда доступен), file - где находится документ с экзаменом, id_group - номер группы.
        //  * @var
        //  * @var
        //  */
        // public function exam_in_groups(Request $request)
        // {
        //     $user = DB::table('labs')
        //         ->join('lab_configs', 'labs.id_lab',  '=', 'lab_configs.id_lab')
        //         ->where('id_discipline',  '=', $request->id_discipline)
        //         ->where('id_form',  '=', '2')
        //         ->select(DB::raw('description, allowed_after, file, id_group'))
        //         ->get();
        //     return self::success("OK", $user);
        // }

        // /**
        //  *  добавить экзамен в дисциплину. Передаем idшник дисциплины(id_discipline), название экзамена(description), file - где будет храниться файл экзамена, comment - если нужен комментарий.
        //  * @var
        //  * @var
        //  */
        // public function insert_exam_in_discipline(Request $request)
        // {
        //     $user = DB::table('labs')
        //         ->insertGetId([
        //             'id_form' => '2',
        //             'id_discipline' => $request->id_discipline,
        //             'description' => $request->id_description,
        //             'file' => $request->file,
        //             'comment' => $request->comment
        //         ]);
        //     return self::success("OK", $user);
        // }

        // /**
        //  *  добавить лабу в дисциплину. Передаем id дисциплины(id_discipline) и название(id_description) соответственно
        //  * @var
        //  * @var
        //  */
        // public function insert_lab_in_discipline(Request $request)
        // {
        //     $user = DB::table('labs')
        //         ->insertGetId([
        //             'id_form' => '1',
        //             'id_discipline' => $request->id_discipline,
        //             'description' => $request->id_description,
        //             'file' => $request->file,
        //             'comment' => $request->comment
        //         ]);
        //     return self::success("OK", $user);
        // }

        // /**
        //  * удалить лабу или экзамен. Передаем id_lab - айдишник лабы/экзамена, т.к. все в одной таблице.
        //  * @var
        //  * @var
        //  */
        // public function delete_lab_or_exam_from_discipline(Request $request)
        // {
        //     $user = DB::table('labs')
        //         ->where('id_lab', '=', $request->id_lab)
        //         ->delete();
        //     return self::success("OK", $user);
        // }

        // /**
        //  * добавить ресурсы. Type - тип ресурса(прога, учебник), location - место куда заливается ресурс, description - название ресурса
        //  * @var
        //  * @var
        //  */
        // public function insert_resources(Request $request)
        // {
        //     $user = DB::table('resources')
        //         ->insertGetId([
        //             'type' => $request->type,
        //             'location' => $request->location,
        //             'description' => $request->description,
        //         ]);
        //     return self::success("OK", $user);
        // }


        // /**
        //  * добавить ресурсы в дисциплину. Передаем id шник дисциплины(id_discipline) и idшник ресурса-дополнительного материала(id_resource), что нужно добавить
        //  * @var
        //  * @var
        //  */
        // public function insert_resources_in_discipline(Request $request)
        // {
        //     $user = DB::table('discipline_resources')
        //         ->insert([
        //             'id_discipline' => $request->id_discipline,
        //             'id_resource' => $request->id_resource,

        //         ]);
        //     return self::success("OK", $user);
        // }

    //! //////////////////////////////////////////////////////
}
