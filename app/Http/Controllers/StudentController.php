<?php

namespace App\Http\Controllers;

use App\Facades\CourseFacade;
use App\Models\CompleteLab;
use App\Models\CourseTheme;
use App\Models\Discipline;
use App\Models\ExamVariant;
use App\Models\Lab;
use App\Models\LabConfig;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use function Safe\date;
use function Safe\strtotime;

class StudentController extends Controller
{
    //? ///////////////////////////////////////////////////
    //? ///////////////////////////////////////////////////
    //? /////////////          AUC           //////////////
    //? ///////////////////////////////////////////////////
    //? ///////////////////////////////////////////////////

    public function getCourses(Request $request)
    {
        return CourseFacade::userCoursesList($request->jwt_user_id);
    }

    //? ///////////////////////////////////////////////////
    //? ///////////////////////////////////////////////////
    //? /////////////        NOT_AUC         //////////////
    //? ///////////////////////////////////////////////////
    //? ///////////////////////////////////////////////////

    // Загружает ответ студента
    public function upload_user_answer(Request $request)
    {
        return FilesController::upload_answer($request);
    }

    // Скачаивает ответ студента
    public function get_user_answer(Request $request)
    {
        $request->student = $request->jwt_user_id;
        return FilesController::download_answer($request);
    }

    // Возвращает данные дисциплин выбранных по семестру для студента
    //TODO: Отслеживание ошибок
    public function disciplines_by_semester(Request $request)
    {
        $group = User::find($request->jwt_user_id)->group;

        $edu_year = intval(($request->semester-1)/2)+$group->admission_year;
        $semester = $request->semester%2;
        if (!$semester) $semester = 2;

        $disciplines = $group->disciplines->where('semester',$semester)->where('edu_year',$edu_year);
        $result = [];
        foreach ($disciplines as $item) {
            $item->discipline->teachers;
            $temp['id_discipline'] = $item->discipline->id_discipline;
            $temp['description'] = $item->discipline->description;
            $teachers = [];
            foreach ($item->discipline->teachers as $teacher) {
                $teachers[] = [
                    'f_name' => $teacher->f_name,
                    's_name' => $teacher->s_name,
                    'fth_name' => $teacher->fth_name,
                ];
            }
            $temp['teachers'] = $teachers;
            $result[] = $temp;
        }

        return self::success("OK",$result);
    }

    public function get_student_discpline(Request $request)
    {
        if (!$student_discipline = User::find($request->jwt_user_id)
            ->student_disciplines->where('id_discipline',$request->discipline)
            ->first()){
            return self::error("NO_SUCH_DISCIPLINE");
        };

        $discipline = $student_discipline->discipline->exam_forms_convert();

        $teachers = $discipline->teachers;
        $result = [];
        foreach ($teachers as $teacher) {
            $temp = $teacher->names();
            $temp->email = $teacher->email;
            $result[]=$temp;
        }

        return self::success("OK",$discipline);
    }

    public function get_teachers(Request $request)
    {
        if (!User::find($request->jwt_user_id)
            ->student_disciplines->where('id_discipline',$request->discipline)
            ->first()){
            return self::error("NO_SUCH_DISCIPLINE");
        };

        $teachers = Discipline::find($request->discipline)->teachers;

        $result = [];
        if ($teachers){
            foreach ($teachers as $teacher) {
                $temp = $teacher->names();
                $temp->email = $teacher->email;
                $result[]=$temp;
            }
        }

        return self::success("OK",$result);
    }

    // Возвращает данные только лабораторных работ по указанной дисциплине
    //TODO: Отслеживание ошибок
    public function get_student_labs(Request $request)
    {
        $user = User::find($request->jwt_user_id);
        if (!$user->student_disciplines->where('id_discipline',$request->discipline)
            ->first()){
            return self::error("NO_SUCH_DISCIPLINE"); //For this user of course ;)
        };

        $labs = Lab::where('id_discipline',$request->discipline)->where('id_form',1)->get();
        foreach ($labs as $lab) {
            $config = $lab->lab_config->where('id_group',$user->id_group)
                ->where('allowed_after','<','CURRENT_TIMESTAMP')->first();
            // if ($lab->file!==null)
            //     $lab->file = $lab->description.'.'.explode('.',$lab->file)[1];
            if ($config){
                $lab->deadline=$config->deadline;
            }
            if ($lab->answer = CompleteLab::where('id_student',$request->jwt_user_id)->where('id_lab',$lab->id_lab)->first()){
                $lab->answer->teacher;
            }
        }


        if (!$labs->first()){
            //Если нет первой то остальных и подавно
            return self::success("NO_LABS");
        }

        return self::success("OK",$labs);
    }

    // Функция для таймлайна, возвращает все лабы из всех предметов отсортированные по дедлайну
    //TODO: Отслеживание ошибок
    public function get_labs_of_current_semester(Request $request)
    {
        // Вычилим текущие год и семестр
        $current_edu_year = date('Y');
        if (intval(date('n'))<8){
            // Значит второй семестр, а год надо указать предыдущий
            $current_semester = 2;
            $current_edu_year--;
        }else{
            $current_semester = 1;
        }

        $user = User::find($request->jwt_user_id);
        $labs = [];
        $lab_configs = LabConfig::where('id_group',$user->id_group)->orderBy('deadline')->get();
        foreach ($lab_configs as $lab_config) {
            $lab = $lab_config->lab;
            if ($lab->id_form===2||$lab->id_form===4||$lab->id_form===5) continue;
            $lab->discipline;
            $year_semester = $lab_config->lab->discipline->students->where('id_group',$user->id_group)->first();

            $lab_edu_year = $year_semester->edu_year;
            $lab_semester = $year_semester->semester;
            if(
                $lab_edu_year == $current_edu_year &&
                $lab_semester == $current_semester
            ){
                $lab->deadline = $lab_config->deadline;
                if ($lab->answer = CompleteLab::where('id_student',$request->jwt_user_id)->where('id_lab',$lab->id_lab)->first()){
                    if ($lab->answer->id_teacher)
                    $lab->answer->teacher = User::find($lab->answer->id_teacher)->names();
                }
                $labs[] =$lab;
            }
        }
        return self::success("OK",$labs);
    }

    /**
     * * Получение номера текущего семестра для студента
     */
    //TODO: Отслеживание ошибок
    public function get_current_semester(Request $request)
    {
        $admission_year = User::find($request->jwt_user_id)->group->admission_year;

        $current_year = date('Y');

        if (intval(date('n'))<8){
            // Значит второй семестр, а год надо указать предыдущий
            $current_semester = 2;
            $current_year--;
        }else{
            $current_semester = 1;
        }

        $semester = ($current_year - $admission_year)*2+$current_semester;

        return self::success("OK",['semester'=>$semester]);
    }

    /**
     *
     */
    //TODO: Отслеживание ошибок и неконтролируемого доступа
    public function get_discipline_resources(Request $request)
    {
        $resources = Discipline::find($request->discipline)->resources;
        return self::success("OK",$resources);
    }

    public function get_sidebar_data(Request $request)
    {
        $user = User::find($request->jwt_user_id);
        $admission_year = $user->group->admission_year;
        $current_year = date('Y');
        if (intval(date('n'))<8){
            $current_semester = 2;
            $current_year--;
        }else{
            $current_semester = 1;
        }

        $student_semester = ($current_year - $admission_year)*2+$current_semester;

        $disciplines = Student::orderBy('semester','DESC')
            ->orderBy('edu_year','DESC')
            ->where('id_group',$user->group->id_group)->get();

        $semesters = [];
        foreach ($disciplines as $discipline) {
            $temp_semester = ($discipline->edu_year - $admission_year)*2+$discipline->semester;
            if (!in_array($temp_semester,$semesters))
                $semesters[] = $temp_semester;
        }

        return self::success("OK",[
            'user'=>$user->names(),
            'menu'=>$semesters,
            'current_semeter'=>$student_semester,
        ]);
    }

    public function get_course_data(Request $request)
    {
        if ($request->discipline===null) return self::error("DISCIPLINE_NOT_PROVIDED","Ошибка выполнения");

        $course = Lab::where('id_discipline',$request->discipline)->where('id_form',2)->first();
        if (!$course) return self::error('NO_COURSE',"Информация о курсовых работах не доступна");

        $user = User::find($request->jwt_user_id);

        $config = $course->lab_config->where('id_group',$user->id_group)->first();
        if (!$config) return self::error('NO_COURSE2',"Информация о курсовых работах не доступна");

        if ($config->allowed_after!==null){
            $allowed_after = $config->allowed_after;
            if (strtotime($config->allowed_after)>time()) {
                return self::success("COURSE_NOT_OPENED_YET",[
                    'allowed_after' => $allowed_after
                ]);
            }
        }else{
            $allowed_after = null;
        }

        // if ($course->file!==null)
        //         $course->file = $course->description.'.'.explode('.',$course->file)[1];

        $course->deadline = $config->deadline;

        $my_theme = CourseTheme::where('id_discipline',$request->discipline)
            ->where('id_student',$request->jwt_user_id)
            ->where('confirmed',1)
            ->first();


        $themes = null;
        if (!$my_theme){
            $themes = [];
            $temp = CourseTheme::where('id_discipline',$request->discipline)
                ->where('confirmed','!=',1)
                ->get();
            foreach ($temp as $theme) {
                if ($theme->id_student === null ||
                    $theme->id_student === $request->jwt_user_id ||
                    $theme->confirmed===-1
                ){
                    $themes[] = $theme;
                }
                if ($theme->id_student === $request->jwt_user_id){
                    $my_theme = $theme;
                }
            }
        }

        if ($my_theme){
            $course->answer = $course->complete_labs->where('id_student',$request->jwt_user_id)->first();
            if ($course->answer)
                $course->answer->teacher;
        }

        return self::success("OK",[
            'themes'=>$themes,
            'myTheme'=>$my_theme,
            'course'=>$course,
        ]);
    }

    public function remove_course_theme(Request $request)
    {
        if ($request->discipline===null) return self::error("DISCIPLINE_NOT_PROVIDED","Ошибка выполнения");

        $my_theme = CourseTheme::where('id_discipline',$request->discipline)
            ->where('id_student',$request->jwt_user_id)
            ->first();

        if (!$my_theme) return self::success("THEME_NOT_SETTED","Ошибка выполнения");
        if ($my_theme->confirmed===1) return self::error("THEME_ALREADY_CONFIRMED");

        $my_theme->id_student = null;
        $my_theme->confirmed = 0;
        $my_theme->save();
        return self::success("OK");
    }

    public function set_course_theme(Request $request)
    {
        if ($request->discipline===null) return self::error("DISCIPLINE_NOT_PROVIDED","Ошибка выполнения");
        if ($request->theme===null) return self::error("THEME_NOT_PROVIDED","Ошибка выполнения");

        $theme = CourseTheme::find($request->theme);
        if (!$theme) return self::success("THEME_NOT_EXIST","Ошибка выполнения");

        $my_theme = CourseTheme::where('id_discipline',$request->discipline)
            ->where('id_student',$request->jwt_user_id)
            ->first();

        if ($my_theme!==null) {
            if ($my_theme->confirmed===1) return self::error("THEME_ALREADY_CONFIRMED");
            $my_theme->id_student=null;
            $my_theme->confirmed = 0;
            $my_theme->save();
        }


        if ($theme->id_student!==null){
            return self::error("ACCESS_DENIED");
        }

        $theme->id_student = $request->jwt_user_id;
        $theme->confirmed = 0;
        $theme->save();
        return self::success("OK");
    }

    public function get_exam_data(Request $request)
    {
        if ($request->discipline===null) return self::error("DISCIPLINE_NOT_PROVIDED","Ошибка выполнения");
        if ($request->examForm===null) return self::error("EXAM_FORM_NOT_PROVIDED","Не установлена форма проведения экзаменации");

        $course = Lab::where('id_discipline',$request->discipline)->where('id_form',$request->examForm)->first();
        if (!$course) return self::error('NO_COURSE',"Информация о курсовых работах не доступна");

        $user = User::find($request->jwt_user_id);

        $config = $course->lab_config->where('id_group',$user->id_group)->first();
        if (!$config) return self::error('NO_COURSE2',"Информация о курсовых работах не доступна");

        if ($config->allowed_after!==null){
            $allowed_after = $config->allowed_after;
            if (strtotime($config->allowed_after)>time()) {
                return self::success("COURSE_NOT_OPENED_YET",[
                    'allowed_after' => $allowed_after
                ]);
            }
        }else{
            $allowed_after = null;
        }

        $course->answer = $course->complete_labs->where('id_student',$request->jwt_user_id)->first();
        if($course->answer) $course->answer->teacher;

        $variant = $course->exam_variants->where('id_student',$request->jwt_user_id)->first();

        return self::success("OK",[
            'allowed_after' => $allowed_after,
            'deadline' => $config->deadline,
            'variant' => $variant,
            'exam'=>$course,
        ]);
    }

    public function get_variant(Request $request)
    {
        if ($request->discipline===null) return self::error("DISCIPLINE_NOT_PROVIDED","Ошибка выполнения");
        if ($request->examForm===null) return self::error("EXAM_FORM_NOT_PROVIDED","Не установлена форма проведения экзаменации");

        $exam = Lab::where('id_discipline',$request->discipline)->where('id_form',$request->examForm)->first();
        if (!$exam) return self::error('NO_COURSE',"Информация о курсовых работах не доступна");

        $user = User::find($request->jwt_user_id);

        $students_in_group = count(User::where('id_group',$user->id_group)->get());

        $config = $exam->lab_config->where('id_group',$user->id_group)->first();
        if (!$config) return self::error('NO_COURSE2',"Информация о курсовых работах не доступна");

        $variant=null;
        do {
            $variant = rand(1,$students_in_group);
        } while (ExamVariant::where(['id_lab'    => $exam->id_lab,'variant'   => $variant,])->first()!==null);

        ExamVariant::create([
            'id_lab'    => $exam->id_lab,
            'id_student'   => $user->id_user,
            'variant'   => $variant,
        ]);

        return self::success("OK",$exam,$variant);
    }

    //! //////////////////////////////////////////////////////////////
    //! //////////////////////////////////////////////////////////////
    //! //////////////////////////////////////////////////////////////
    //! //////////////////////////////////////////////////////////////
    //! //////////////////////////////////////////////////////////////

}
