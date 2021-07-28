<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Exception;

class BooleanSwitchers //extends Model
{
    /**
     * Exception codes
     * 0 = "Switchers config must have array type. Provided Config:".print_r($config_array,true)
     * 1 = "No Switchers Provided" or "Empty switchers Config Array provided."
     * 2 - "Switcher is not exist in Config Array"
     * 3 - "Switcher must have string or integer value"
     * 4 - "Resulting Switchers less than 0." - Unexpected error
     */


    public static function convert_switchers($input,$config_array = [])
    {
        if (!is_array($config_array)) throw new Exception("Switchers config must have array type. Provided Config:".print_r($config_array,true), 0);
        if (empty($config_array)) throw new Exception("Empty switchers Config Array provided. Config:".print_r($config_array,true), 1);

        if (is_numeric($input)){
            // предосталено число, преобразуем в массив
            return BooleanSwitchers::convert_to_array($input,$config_array);
        }

        // предосталено что-то другое, пробуем преобразовать в число
        return BooleanSwitchers::convert_to_int($input,$config_array);

        // //Если не число и не массив генерируем ошибку
        // throw new Exception("SWITCHERS:NOTHING_TO_CONVERT", 1);

    }

    //-----------------------------------------------------------------------------------------------

    /**
     * Преоборазовывает переключатели, заданные числом в массив
     *
     * @param   integer     переключатели числом соответствующее
     *                      двочному переключателю булевых значений
     *
     * @return  array       переключатели как массив из строк
     */
    private static function convert_to_array( $switchers_num = 0, $config_array = [] )
    {
        if (!is_array($config_array)) throw new Exception("Switchers config must have array type. Provided Config:".print_r($config_array,true), 0);
        if (empty($config_array)) throw new Exception("Empty switchers Config Array provided. Config:".print_r($config_array,true), 1);
        if ($switchers_num==0) return [];

        $switchers_array = [];
        for ($i=count($config_array); $i >= 0 ; $i--) {
            if ($switchers_num-(int)pow(2,$i)>=0)
                {
                    $switchers_array[] = $config_array[$i];
                    $switchers_num-=(int)pow(2,$i);
                }
        }
        return $switchers_array;
    }

    //-----------------------------------------------------------------------------------------------

    /**
     * Преоборазовывает переключатели, заданные любым образом в число
     *
     * @param   mixed       переключатели массивом из чисел или строк, номер переключатели или строка названия переключатели
     *
     * @return  integer     переключатели числом
     * @throws  Exception   При ошибке
     */
    private static function convert_to_int( $switchers, $config_array = []  )
    {
        if (!is_array($config_array)) throw new Exception("Switchers config must have array type. Provided Config:".print_r($config_array,true), 0);
        if (empty($config_array)) throw new Exception("Empty switchers Config Array provided. Config:".print_r($config_array,true), 1);
        $global_switchers=$config_array;
        foreach ($global_switchers as $key => $value) {
            $global_switchers_invert[$value]=$key;
        }

        //Если переключатели заданы массивом, тогда необходимо обработать массив
        if (is_array($switchers)){
            foreach ($switchers as $key => $value) {
                //Если переключатели в массиве представлены в виде строк, тогда конвертируем эти строки в числа
                if (is_string($value)){
                    if (isset($global_switchers_invert[$value])){
                        //получена строка, существующего переключателя => конвертация
                        $switchers[$key] = $global_switchers_invert[$value];
                    }else{
                        //Получена строка несуществующего переключателя => ошибка =>> FALSE
                        throw new Exception("Switcher \"".$value."\" is not exist in Config Array", 2);
                        // return FALSE;
                    }
                }else if(is_numeric($value)){
                    //Если это не строка, а число, тогда просто проверим правильность числа в конфиге
                    if (!isset($global_switchers[$value])){
                        //Если число не найдено в конфиге => ошибка =>> FALSE;
                        throw new Exception("Switcher \"".$value."\" is not exist in Config Array", 2);
                        // return FALSE;
                    }
                }else{
                    //А если получено не число и не строка => тоже ошибка =>> FALSE
                    throw new Exception("Switcher \"".$value."\" must have string or integer value", 3);
                    // return FALSE;
                }
            }
        }else{
            //Если данные пришли в виде одиночного числа или строки
            if (is_string($switchers)){
                if (isset($global_switchers_invert[$switchers])){
                    //получена строка, существующей переключатели => конвертация
                    $temp=array();
                    $temp[] = $global_switchers_invert[$switchers];
                    $switchers=$temp;
                }else{
                    //Получена строка несуществующей переключатели => ошибка =>> FALSE
                    throw new Exception("Switcher \"".$value."\" is not exist in Config Array", 2);
                        // return FALSE;
                }
            }else if(is_numeric($switchers)){
                //Если это не строка, а число, тогда просто проверим правильность числа в конфиге
                if (!isset($global_switchers[$switchers])){
                    //Если число не найдено в конфиге => ошибка =>> FALSE;
                    throw new Exception("Switcher \"".$value."\" is not exist in Config Array", 2);
                    // return FALSE;
                }else{
                    //Если в конфиге есть такое число, тогда преобразуем его в массив из одного числа
                    $temp=array();
                    $temp[] = $switchers;
                    $switchers=$temp;
                }
            }else{
                //А если получено не число и не строка => тоже ошибка =>> FALSE
                throw new Exception("Switcher \"".$value."\" must have string or integer value", 3);
                // return FALSE;
            }
        }

        //После такой обработки должны иметь массив, состоящий из чисел
        //Однако необходимо удалить повторяющиеся числа
        $switchers=array_unique($switchers);

        $switchers_num=0;

        //Преобразование переключателей в число
        foreach ($switchers as $key => $value) {
            $switchers_num+=(int)pow(2,$value);
        }

        return $switchers_num;

    }

    //-----------------------------------------------------------------------------------------------

    /**
     * Сравнение переключателей числами
     *
     * @param   int:existed_switchers   имеющиеся переключатели числом
     * @param   int:required_switchers  необходимые переключатели числом
     *
     * @return  bool   TRUE, если необходимые переключатели входят в имеющиеся, иначе - FALSE
     */
    public static function compare_switchers(int $existed_switchers = -1, int $required_switchers = -1, $config_array = []  )
    {
        if (!is_array($config_array)) throw new Exception("Switchers config must have array type. Provided Config:".print_r($config_array,true), 0);
        if (empty($config_array)) throw new Exception("Empty switchers Config Array provided. Config:".print_r($config_array,true), 1);
        //Если нет входных данных =>> FALSE
        if ($existed_switchers<0||$required_switchers<0) throw new Exception("No Switchers Provided", 1);

        //Если необходимые переключатели = 0, то любые подходят =>> TRUE
        if ($required_switchers==0) {
            return TRUE;
        }
        //Если необходимые переключатели >0, а имеющиеся ==0
        if ($existed_switchers==0) {
            //Тогда переключатели уже не подходят =>> FALSE
            return FALSE;
        }

        //Если все не так очевидно, необходимо конвертировать переключатели в массивы
        $ext_array = BooleanSwitchers::convert_to_array($existed_switchers, $config_array);
        $req_array = BooleanSwitchers::convert_to_array($required_switchers, $config_array);

        //Теперь проверим, если окажется что какая-либо необходимая переключатель (value)
        foreach ($req_array as $key => $value) {

            // не входит в имеющиеся =>> FALSE
            if ( ! in_array( $value, $ext_array ) ){
                return FALSE;
            }

        }

        //А после проверки, вернем =>> TRUE
        return TRUE;

    }

    //-----------------------------------------------------------------------------------------------

    /**
     * Проверка имеются запрашиваемые переключатели среди требуемых
     *
     * @param   mixed   переключатели числом либо массивом
     * @param   mixed   переключатели числом либо названием
     *
     * @return  bool    TRUE, если имеет, иначе - FALSE
     */
    public static function has_switcher( $required_switchers = -1, $existed_switchers = -1, $config_array = [] )
    {
        if (!is_array($config_array)) throw new Exception("Switchers config must have array type. Provided Config:".print_r($config_array,true), 0);
        if (empty($config_array)) throw new Exception("Empty switchers Config Array provided. Config:".print_r($config_array,true), 1);
        //Если нет входных данных
        if ($required_switchers<0||$existed_switchers<0) throw new Exception("No Switchers Provided", 1);

        //Если Привелегии ==0 или пустой массив =>> TRUE
        if ((is_numeric($required_switchers)&&$required_switchers===0)||(is_array($required_switchers)&&empty($required_switchers))) return TRUE;

        //Преобразовать входящие привелегии в число
        $required_switchers_int = BooleanSwitchers::convert_to_int($required_switchers,$config_array);

        //Сравнить привелегии числами
        return BooleanSwitchers::compare_switchers($existed_switchers,$required_switchers_int,$config_array);

    }

    /**
     * Произвоит добавление нового переключателя к набору существующих
     * Возвращает TRUE при успехе и FALSE при неудаче
     */
    public static function add_switcher_to_existed(int $new_switcher = -1,int $existed_switchers = -1, $config_array = [] )
    {
        if (!is_array($config_array)) throw new Exception("Switchers config must have array type. Provided Config:".print_r($config_array,true), 0);
        if (empty($config_array)) throw new Exception("Empty switchers Config Array provided. Config:".print_r($config_array,true), 1);
        //Если нет входных данных
        if ($new_switcher<0||$existed_switchers<0) throw new Exception("No Switchers Provided", 1);

        //Не существует такой переключатели в конфиге
        if (!isset($config_array[$new_switcher]))
            throw new Exception("Switcher \"".$new_switcher."\" is not exist in Config Array", 2);

        //Если переключатель уже есть =>> TRUE
        if (BooleanSwitchers::has_switcher($new_switcher,$existed_switchers, $config_array)) return $existed_switchers;

        //Преобразовать входящие переключатели в число и добавить к текущим
        $new_switchers = $existed_switchers + BooleanSwitchers::convert_to_int($new_switcher, $config_array);

        // Вернуть получившиееся число
        return $new_switchers;

    }


    //------------------------------------------------------------


    /**
     * Произвоит удаление переключателя из набора существующих
     * Возвращает число - новые переключатели
     */
    public static function remove_switcher_from_existed(int $removing_switcher = -1,int $existed_switchers = -1, $config_array = [] )
    {
        if (!is_array($config_array)) throw new Exception("Switchers config must have array type. Provided Config:".print_r($config_array,true), 0);
        if (empty($config_array)) throw new Exception("Empty switchers Config Array provided. Config:".print_r($config_array,true), 1);
        //Если нет входных данных
        if ($removing_switcher<0||$existed_switchers<0) throw new Exception("No Switchers Provided", 1);

        //Не существует такой переключателя в конфиге
        if (!isset($config_array[$removing_switcher]))
            throw new Exception("Switcher \"".$removing_switcher."\" is not exist in Config Array", 2);

        //Если переключателя уже нет =>> TRUE
        if (!BooleanSwitchers::has_switcher($removing_switcher,$existed_switchers, $config_array)) return $existed_switchers;

        //Преобразовать входящие переключатели в число и добавить к текущим
        $new_switchers = $existed_switchers - BooleanSwitchers::convert_to_int($removing_switcher, $config_array);

        //Произошла ошибка
        if ($new_switchers<0) throw new Exception("Resulting Switchers less than 0. New Switchers: ".$new_switchers, 4);

        // Вернуть получившиееся число
        return $new_switchers;
    }
}
