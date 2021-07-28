<?php

namespace App\Http\Controllers;

use App\Models\CompleteLab;
use App\Models\Discipline;
use App\Models\DisciplineResource;
use App\Models\Group;
use App\Models\Lab;
use App\Models\LabConfig;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Safe\date;

class FilesController extends Controller
{
    //* ////////////////////////////////////////////////////////////
    //* ////////////                              //////////////////
    //* ////////////            ОТВЕТЫ            //////////////////
    //* ////////////                              //////////////////
    //* ////////////////////////////////////////////////////////////

        static $answers_folder = 'student_answers/';

        /**
         * Загружает на сервер полученный файл и
         * сохраняет/заменяет как файл ответа на указанной по id лабу
         * @var request->lab - id лабы в таблице
         * @var request->jwt_user_role===student - требуется роль пользователя Студент
         * @var request->file
         *
         * @return response::success=ANSWER_UPLOADED 200
         * @return response::success=ANSWER_UPLOADED_N_UPDATED 200
         * @throws response::error=ACCESS_DENIED 200
         */
        public static function upload_answer(Request $request)
        {
            if ($request->jwt_user_role!=='student') return self::error("ACCESS_DENIED",'Only students can upload their answers');

            $originalname = $request->file('file')->getClientOriginalName();
            $path = $request->file('file')->storeAs(
                FilesController::$answers_folder.$request->jwt_user_id,
                Str::random(3).'_'.$originalname,
                'local'
            );

            $answer = CompleteLab::where([
                'id_student' => $request->jwt_user_id,
                'id_lab' => $request->lab
            ])->first();

            if (!$answer){
                $result = CompleteLab::create([
                    'id_student' => $request->jwt_user_id,
                    'id_lab' => $request->lab,
                    'complete_date' => date('Y-m-d H:i:s', time()),
                    'file' => $path,
                    'status'=>1,
                ]);
                return self::success("ANSWER_UPLOADED",[
                    'file' => $path,
                    'status'=>$result->status,
                ]);
            }else{
                if ($answer->status==2||$answer->status==3){
                    $answer->status = 3;
                }
                if (Storage::disk('local')->exists($answer->file)) {
                    Storage::delete($answer->file);
                }
                CompleteLab::where([
                    'id_student' => $request->jwt_user_id,
                    'id_lab' => $request->lab
                ])
                    ->update([
                        'complete_date' => date('Y-m-d H:i:s', time()),
                        'file' => $path,
                        'status'=>$answer->status,
                    ]);
                return self::success("ANSWER_UPLOADED_N_UPDATED",[
                    'file' => $path,
                    'status'=>$answer->status,
                ]);
            }
        }

        /**
         * Скачивает файл ответа по id lab и id student
         * @var request->lab - id лабы
         * @var request->student - id студента
         *
         * @return Storage::download 200
         * @throws response::error=NO_LAB_PROVIDED 404
         * @throws response::error=NO_STUDENT_PROVIDED 404
         * @throws response::error=ANSWER_NOT_EXIST 404
         * @throws response::error=FILE_NOT_FOUND 404
         */
        public static function download_answer(Request $request)
        {
            if (!$request->lab) return self::error("NO_LAB_PROVIDED",null,[],404);
            if (!$request->student) return self::error("NO_STUDENT_PROVIDED",null,[],404);

            $answer = CompleteLab::where([
                'id_student' => $request->student,
                'id_lab' => $request->lab
            ])->first();

            if (!$answer) return self::error("ANSWER_NOT_EXIST",null,[],404);

            if (Storage::disk('local')->exists($answer->file)) {
                return Storage::download($answer->file,$answer->description,
                ['Content-Disposition' => "attachment;filename=".$answer->description,
                'Content-Type' => "application/octet-stream"]);
            }else{
                return self::error("FILE_NOT_FOUND",null,[],404);
            }
        }

    //* ////////////////////////////////////////////////////////////
    //* ////////////                              //////////////////
    //* ////////////             ЛАБЫ             //////////////////
    //* ////////////                              //////////////////
    //* ////////////////////////////////////////////////////////////

        static $lab_folder = 'labs/';

        /**
         * Скачивает файл задания лабораторной/экзамена/ и т.д указанный по id_lab
         * @var request->lab - id лабы в таблице
         *
         * @return Storage::download
         * @throws response::error=NO_LAB_PROVIDED 404
         * @throws response::error=FILE_NOT_FOUND 404
         */
        public static function download_lab(Request $request)
        {
            if (!$request->lab) return self::error("NO_LAB_PROVIDED",null,[],404);
            $lab = Lab::where([
                'id_lab' => $request->lab
            ])->first();

            if (Storage::disk('local')->exists($lab->file)) {
                return Storage::download($lab->file,'Файл задания '.$lab->description,
                ['Content-Disposition' => "attachment;filename=Файл задания ".$lab->description,
                'Content-Type' => "application/octet-stream"]);
            }else{
                return self::error("FILE_NOT_FOUND",null,[],404);
            }

        }


        /**
         * Загружает на сервер полученный файл и сохраняет/заменяет как файл лабы указанной по id_lab
         * @var request->lab - id лабы в таблице
         * @var request->file
         *
         * @return response::success=LAB_UPLOADED 200
         * @throws response::error=NO_LAB_PROVIDED 200
         * @throws response::error=LAB_NOT_EXIST 200
         * @throws response::error=FILE_NOT_FOUND 200
         */
        public static function upload_lab(Request $request, $simple_return = FALSE)
        {
            if (!$request->lab) {
                if ($simple_return) return "NO_LAB_PROVIDED";
                return self::error("NO_LAB_PROVIDED");
            }
            if (!$request->file) {
                if ($simple_return) return "NO_FILE_PROVIDED";
                return self::error("NO_FILE_PROVIDED");
            }

            $lab = Lab::where([
                'id_lab' => $request->lab
            ])->first();

            if (!$lab) {
                if ($simple_return) return "LAB_NOT_EXIST";
                return self::error("LAB_NOT_EXIST");
            }

            if (Storage::disk('local')->exists($lab->file)) {
                Storage::delete($lab->file);
            }

            $originalname = $request->file('file')->getClientOriginalName();
            $path = $request->file('file')->storeAs(
                FilesController::$lab_folder.$request->jwt_user_id,
                Str::random(3).'_'.$originalname,
                'local'
            );

            $lab->file = $path;
            $lab->save();

            if ($simple_return) return "LAB_UPLOADED";
            return self::success("LAB_UPLOADED");
        }


    //* ////////////////////////////////////////////////////////////
    //* ////////////                              //////////////////
    //* ////////////            РЕСУРСЫ           //////////////////
    //* ////////////                              //////////////////
    //* ////////////////////////////////////////////////////////////

        static $resource_folder = 'resources/';

        /**
         * Скачивает ресурс указанный по id
         * @var request->resource - id ресурса в таблице
         *
         * @return Storage::download
         * @throws response::error=NO_RESOURCE_PROVIDED 404
         * @throws response::error=RESOURCE_NOT_EXIST 404
         * @throws response::error=FILE_NOT_FOUND 404
         */
        public static function download_resource(Request $request)
        {
            if (!$request->resource) return self::error("NO_RESOURCE_PROVIDED",null,[],404);

            $resource = Resource::where([
                'id_resource' => $request->resource
            ])->first();

            if (!$resource) return self::error("RESOURCE_NOT_EXIST",null,[],404);
            if (Storage::disk('local')->exists($resource->file)) {
                return Storage::download($resource->file,$resource->description,
                ['Content-Disposition' => "attachment;filename=".$resource->description,
                'Content-Type' => "application/octet-stream"]);
            }else{
                return self::error("FILE_NOT_FOUND",null,[],404);
            }
        }

        /**
         * Загружает указанный файл как ресурс для указанной дисциплины
         * @var request->jwt_user_id
         * @var request->discipline
         * @var request->type
         * @var request->file
         *
         * @return response::success=RESOURCE_UPLOADED
         */
        public static function upload_resource(Request $request)
        {
            $path = $request->file('file')->store(
                FilesController::$resource_folder.$request->jwt_user_id,
                'local'
            );

            // Создает запись о загруженном ресурсе
            Resource::create([
                'file' => $path,
                'description' => $request->file('file')->getClientOriginalName(),
                'type' => 2, //TODO: Установить кастомный тип ресурса
            ]);

            $resource = Resource::where([
                'file' => $path,
                'description' => $request->file('file')->getClientOriginalName(),
            ])->first();

            DisciplineResource::create([
                'id_resource' => $resource->id_resource,
                'id_discipline' => $request->discipline,
            ]);

            return self::success("RESOURCE_UPLOADED");
            // ,[
            //     'path' => $path,
            //     'resource'=>$resource,
            // ]);

        }
}
