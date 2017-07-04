<?php

namespace App\Tool;

use App\Paper;
use App\Question;
use App\Tool\QuestionTool;
class PaperTool{
    public static function deal($paper, $question = false, $option = false){
         $array = $paper->attributesToArray();
         unset($array['password']);
         $array['subject_name'] = $paper->subject->name;
         if($question){
             $data = QuestionTool::dealAll($paper->questions, $option);
             $array = array_merge($array, $data);
         }

         return $array;
    }

    public static function dealAll($papers, $question = false, $option = false){
        $result = ['papers' => []];

        if(method_exists($papers, 'currentPage')){
            $result['curPage'] = $papers->currentPage();
            $result['count']   = $papers->count();
            $result['lastPage']= $papers->lastPage();
            $result['perPage'] = $papers->perPage();
        }

        foreach($papers as $paper){
            $result['papers'][] = self::deal($paper, $question, $option);
        }

        return $result;
    }

    public static function join(){
        return Paper::join('totalpq', 'paper.id', '=', 'totalpq.paper_id')
             ->join( 'question','question.id', '=', 'totalpq.question_id')
             ->join('teacher_record','teacher_record.pq_id','=','totalpq.id')
             ->join('student_record','teacher_record.student_record_id','=','student_record.id');
    }
}
