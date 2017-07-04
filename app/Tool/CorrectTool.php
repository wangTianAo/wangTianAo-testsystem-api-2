<?php

namespace App\Tool;

use App\Paper;
use App\Question;
use App\Tool\QuestionTool;
use Illuminate\Support\Facades\DB;
class CorrectTool{
    public static function insert($tid, $sid, $qid, $pid, $has_correct = 0, $grade = 0, $description = NULL){
        $pq_id = DB::table('totalpq')->where([
                    ['totalpq.question_id',$qid],
                    ['totalpq.paper_id',$pid]
                ])->select('totalpq.id')->get()->first()->id;

        $student_record_id = DB::table('student_record')->insertGetId([
                'student_id'=>$sid,
                'pq_id'=> $pq_id,
                'description'=>$description
            ]);

            DB::table('teacher_record')->insertGetId([
                    'teacher_id'=>$tid,
                    'pq_id'=>$pq_id,
                    'grade'=>$grade,
                    'has_correct'=>$has_correct,
                    'student_record_id'=>$student_record_id
                  ]);
            return ;
    }

    public static function update($tid, $sid, $qid, $pid, $has_correct = 1, $grade = 0){
        $pq_id = DB::table('totalpq')->where([
               ['totalpq.question_id',$qid],
               ['totalpq.paper_id',$pid]
               ])->select('totalpq.id')->get()->first()->id;

        $student_record_id = DB::table('student_record')->where([
             ['student_id',$sid],
             ['pq_id',$pq_id]
         ])->select('student_record.id')->get()->first()->id;

        DB::table('teacher_record')->where([
               ['teacher_record.student_record_id',$student_record_id]
         ])->update([
               'grade' => $grade,
               'has_correct' => $has_correct
         ]);

        return;
    }

    public static function verify_answer($id, $replys){
        $question = Question::where('id', $id)->first();
        $replys = array_unique($replys);
        $corrects = [];

        foreach($question->options as $option){
            if($option->correct){
              $corrects[] = $option->id;
            }
        }

        if(count($corrects) !== count($replys))
            return FALSE;

        foreach($replys as $reply){
          if(!in_array($reply, $corrects)){
              return FALSE;
          }
        }

        return TRUE;
    }
}
