<?php
    namespace App;

    use Illuminate\Database\Eloquent\Model;

    class Record extends Model {
        public $timestamps = false;

        protected $table = 'record';

        protected $fillable = ['id','student_id', 'question_id','paper_id'];

        protected $dates = [];

        public static $rules = [
            // Validation rules
        ];

        public function student(){
            $this->hasOne('App\Student');
        }

        public function question(){
            $this->hasOne('App\Question');
        }

        public function paper(){
            $this->hasOne('App\Paper');
        }
    }
?>