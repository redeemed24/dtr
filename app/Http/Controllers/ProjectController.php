<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Project;
use App\User;
use App\Dev;
use Auth;
use Validator;
use Response;
use Carbon\Carbon;

class ProjectController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }
    
    public function show($id)
    {
        $project = Project::find($id);
        
        return $project;//view('project',compact('project'));
    }

    public function addProject(Request $request)
    { 
        $data = new Project();
        $data->name = $request->name;
        $data->added_by = $request->added_by;
        $data->pm_id = $request->pm_id;
        $data->tl_id = $request->tl_id;
        $data->date_created = Carbon::now()->toDateString();
        $data->save();
        
        $dev = new Dev();
        $dev->dev_id = $request->tl_id;
        $dev->proj_id = $data->id;
        $dev->date_created = Carbon::now()->toDateString();
        $dev->save();
        return response()->json($data);  
    }
    
    public function getQuery(Request $req)
    {
        $id = Auth::id();
        
        if (User::find($id)->type == 'Dev')
        {
            $project = Dev::where('dev_id', $id)->get();
            $projDevArray = [];
            $projDevArray = array_pluck($project, 'proj_id');
            $project = Project::whereIn('id', $projDevArray)->get();
        }
        else if (User::find($id)->type == 'Admin')
        {
            $project = DB::table('projects')
            ->select(DB::raw('projects.*, COUNT(devs.dev_id) as total_devs, COUNT(dtrs.task_no) as total_tickets'))
            ->leftJoin('devs', 'devs.proj_id', '=', 'projects.id')
            ->leftJoin('dtrs', 'devs.id', '=', 'dtrs.proj_devs_id')
            ->groupBy('projects.id')
            ->get();
        }
        else
        {
            $project = Project::all();
        }
        
        $projectCount = count($project);

        $dev = User::where('type', 'Dev')->get();
        $pm = User::orderByRaw("id = $id DESC")->where('type', 'PM')->get();
        $allPM = User::where('type', 'PM')->get();
        return view ('dashboard',compact('project', 'projectCount', 'dev', 'pm', 'allPM'));
    }
    
    public function updateProject(Request $req) 
    {
        $data = Project::find($req->id);
        $data->name = $req->name;
        $data->pm_id = $req->pm_id;
        $data->tl_id = $req->tl_id;
        $data->save();
        return response()->json($data);
    }
    
    public function deleteProject(Request $req) {
        Project::find($req->id)->delete();
        return response()->json();
    }
    
    public function getProject(Request $req)
    {
        $projectItem = new Project();
        $projectItem = Project::find( $req->id);
        $projectItem->pm = $projectItem->PM->name;
        $projectItem->tl = $projectItem->TL->name;
        return (object)$projectItem;
    }
    
    public function getDev(Request $req)
    {
        $project = Project::find( $req->id);
        $dev = $project->dev;
        $devArray = [];
        $devArray = array_pluck($dev, 'dev_id');

        $selectDev = User::where([
            ['id', '!=', $project->tl_id],
            ['type', 'Dev']])
            ->whereNotIn('id', $devArray)
            ->get();

          return (object)$selectDev;
    }
    
}
