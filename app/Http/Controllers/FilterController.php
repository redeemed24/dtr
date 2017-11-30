<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;
use Validator;
use Response;
use Carbon\Carbon;
use App\Project;


class FilterController extends Controller
{
    public function getQuery(Request $request, $option = null)
    {
        $getDtrIds = DB::table('dtrs')->pluck('proj_devs_id');
        $current_time = Carbon::now()->toDateString();
        $getDevsDtrs = DB::table('devs')->select('dev_id')->groupBy('dev_id')->get();
        $project = Project::all();

        $query = DB::table('users')
            ->join('devs', 'users.id', '=', 'devs.dev_id')
            ->join('projects', 'devs.proj_id', '=', 'projects.id')
            ->leftJoin('dtrs', 'devs.id', '=', 'dtrs.proj_devs_id');

            if(Auth::user()->type === 'Admin' && $option === null){                
                $query->select('users.id','users.name', 'projects.id as project_id','projects.name as project_name', 'dtrs.ticket_no', 
                    'dtrs.task_title', 'dtrs.hours_rendered', 'dtrs.date_created', 'dtrs.roadblock')
                    ->where('users.type', 'Dev')
                    ->where('dtrs.date_created', $current_time)
                    ->whereIn('devs.id', $getDtrIds);
            }else if(Auth::user()->type === 'Dev' && $option === null){     
                $query->select('users.id','users.name', 'projects.id as project_id','projects.name as project_name', 'dtrs.ticket_no', 
                    'dtrs.task_title', 'dtrs.hours_rendered', 'dtrs.date_created', 'dtrs.roadblock')
                    ->where('dtrs.date_created', $current_time)
                    ->where('users.id', Auth::id());
            }else if(Auth::user()->type === 'PM' && $option === null){                
                $query->select('users.id','users.name', 'projects.id as project_id','projects.name as project_name', 'dtrs.ticket_no', 
                    'dtrs.task_title', 'dtrs.hours_rendered', 'dtrs.date_created', 'dtrs.roadblock')
                    ->where('users.type', 'Dev')
                    ->where('dtrs.date_created', $current_time)
                    ->whereIn('devs.id', $getDtrIds);
            }

        if($option === null){
            $result = $query->get();
            return view('reports', compact('result', 'getDevsDtrs', 'project'));
        } else if($option === 'total_hours_per_project'){
            $query->select('users.id', 'projects.id as project_id','projects.name as project_name', 'dtrs.ticket_no', 
                'dtrs.task_title', DB::raw('SUM(dtrs.hours_rendered) as hours_rendered'), 'dtrs.date_created', 'dtrs.roadblock')
                ->where('users.id', Auth::id())
                ->groupby('users.id', 'projects.id');

            if($request->start && $request->end){
                $query->whereBetween('dtrs.date_created', [$request->start, $request->end]);
            }else $query->where('dtrs.date_created', $current_time);

            $result = $query->get();
            $data = array();
            $graph = array();

            if($result){                
                foreach ($result as $key => $value) {
                    $data['labels'][] = $result[$key]->project_name;
                    $data['hours_rendered'][] = $result[$key]->hours_rendered;
                    $data['colors'][] = '#'.$this->random_color_part() . $this->random_color_part() . $this->random_color_part();
                }
            }
            
            if($data){
                $graph = array(
                    'data' => array(
                        'labels' => $data['labels'], 
                        'datasets' => array(
                                array(
                                    'label' => 'Total Hour per Project', 
                                    'data' => $data['hours_rendered'], 
                                    'backgroundColor' => $data['colors'], 
                                    'borderWidth' => 1
                                )
                            )
                        ),
                    'options' => array(
                            'scales' => array(
                                'yAxes' => [array(
                                    'ticks' => array('beginAtZero' => true),
                                    )]
                                ) 
                        )
                    );
            }

            echo json_encode($graph);
        }
    }
    
    public function getFilter(Request $request)
    {
        $groupby = $request->groupby;
        $start = $request->starts;
        $end = $request->ends;
        $getDtrIds = DB::table('dtrs')->pluck('proj_devs_id');
        
        switch($groupby){
        
            case 'Group by Developers' :
            
                $query = DB::table('users')
                    ->select('users.id','users.name', 'projects.name as project_name', 'dtrs.ticket_no', 
                             'dtrs.task_title', 'dtrs.hours_rendered', 'dtrs.date_created', 'dtrs.roadblock', 'dtrs.hours_rendered')
                    ->leftJoin('devs', 'users.id', '=', 'devs.dev_id')
                    ->leftJoin('projects', 'devs.proj_id', '=', 'projects.id')
                    ->leftJoin('dtrs', 'devs.id', '=', 'dtrs.proj_devs_id');

                if(Auth::user()->type === 'Admin'){
                    $query->where('users.type', 'Dev')
                        ->whereBetween('dtrs.date_created', [$start, $end])
                        ->whereIn('devs.id', $getDtrIds)
                        ->groupBy('dtrs.id');
                }else if(Auth::user()->type === 'Dev'){                    
                    $query->where('users.id', Auth::id())
                        ->whereBetween('dtrs.date_created', [$start, $end])
                        ->groupBy('dtrs.id');
                }

                $result = $query->get();

                return $result;
            
            break;
            
            case 'Group by Projects' :
            
                $query = DB::table('users')
                    ->select('projects.id','projects.name', 'users.name as username', 'dtrs.ticket_no', 
                             'dtrs.task_title', 'dtrs.hours_rendered', 'dtrs.date_created', 'dtrs.roadblock', 'dtrs.hours_rendered')
                    ->leftJoin('devs', 'users.id', '=', 'devs.dev_id')
                    ->leftJoin('projects', 'devs.proj_id', '=', 'projects.id')
                    ->leftJoin('dtrs', 'devs.id', '=', 'dtrs.proj_devs_id')
                    ->groupBy('dtrs.id');

                    if(Auth::user()->type === 'Admin'){
                        $query->where('users.type', 'Dev')
                            ->whereBetween('dtrs.date_created', [$start, $end])
                            ->whereIn('devs.id', $getDtrIds);
                    }else if(Auth::user()->type === 'Dev'){                    
                        $query->where('users.id', Auth::id())
                            ->whereBetween('dtrs.date_created', [$start, $end]);
                    }

                    $result = $query->get();

                return $result;
            
            break;
            
            case 'Group by Tickets' :
            
                $query = DB::table('users')
                    ->select('dtrs.ticket_no as id','projects.name as project_name', 'users.name as username',
                             'dtrs.task_title as name', 'dtrs.hours_rendered', 'dtrs.date_created', 'dtrs.roadblock', 'dtrs.hours_rendered')
                    ->leftJoin('devs', 'users.id', '=', 'devs.dev_id')
                    ->leftJoin('projects', 'devs.proj_id', '=', 'projects.id')
                    ->leftJoin('dtrs', 'devs.id', '=', 'dtrs.proj_devs_id')
                    ->groupBy('dtrs.id');

                if(Auth::user()->type === 'Admin'){
                    $query->where('users.type', 'Dev')
                        ->whereBetween('dtrs.date_created', [$start, $end])
                        ->whereIn('devs.id', $getDtrIds);
                }else if(Auth::user()->type === 'Dev'){                    
                    $query->where('users.id', Auth::id())
                        ->whereBetween('dtrs.date_created', [$start, $end]);
                }

                $result = $query->get();

                return $result;
            
            break;
        
        }
    
    }

    function random_color_part() {
        return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }

}
