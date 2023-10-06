<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use App\Machines;
use DB;
use File;
use Datatables;
// use Yajra\DataTables\Facades\DataTables;
use App\Utils\ProductUtil;

class MachineController extends Controller
{

    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('machines.view') && !auth()->user()->can('machines.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $machines = Machines::select('image', 'machine_name', 'machine_condition', 'id')->get();
            $condition = request()->get('condition', null);
            if (!empty($condition)) {
                $machines = $machines->where('machine_condition', $condition);
            }


            return Datatables::of($machines)
            
                ->addColumn(
                    'action',
                    '@can("machines.update")
                <button data-val="{{ $id }}" class="btn btn-xs btn-primary edit_machine_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                &nbsp;
                @endcan
                @can("machines.delete")
                <a><button data-href="{{ action(\'MachineController@destroy\', [$id]) }}" class="btn btn-xs btn-danger delete_machine_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button></a>
                @endcan'
                )
                ->addColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . asset('uploads/machines_images/' . $row->image . '') . '" alt="" class="machine-thumbnail-small" style="object-fit: cover;"></div>';
                })
                ->editColumn('machine_name', function ($row) {
                    return  $row->machine_name;
                })
                // ->editColumn('machine_condition', function ($row) {
                //     return  $row->machine_condition;
                // })
                ->addColumn('machine_condition', function ($row) {
                    if ($row->machine_condition == 'good') {
                        return '<button class="btn btn-xs btn-success update_status">Good</button>';
                    } else if ($row->machine_condition == 'medium') {
                        return '<button class="btn btn-xs btn-info update_status">Medium</button>';
                    } else if ($row->machine_condition == 'low') {
                        return '<button class="btn btn-xs btn-warning update_status">Low</button>';
                    } else {
                        return '<button class="btn btn-xs btn-danger update_status">Need to repaire</button>';
                    }
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'image', 'machine_condition'])
                ->make(true);
        }

        return view('machines.index');
    }

    public function create()
    {
        if (!auth()->user()->can('machines.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('machines.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        if (!auth()->user()->can('machines.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {

            //upload document
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/uploads/machines_images');
                $image->move($destinationPath, $name);

                $machines = new Machines;
                $machines->machine_name = $request->machine_name;
                $machines->machine_condition = $request->machine_condition;
                $machines->image = $name;
                $machines->save();
            } else {
                $machines = new Machines;
                $machines->machine_name = $request->machine_name;
                $machines->machine_condition = $request->machine_condition;
                $machines->save();
            }

            $output = [
                'success' => true,
                'msg' => __("lang_v1.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return redirect()->back()->with('status', $output);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        if (!auth()->user()->can('brand.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $customer_group = Machines::find($id);

            return view('machines.edit')
                ->with(compact('customer_group'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updates(Request $request)
    {
        if (!auth()->user()->can('machines.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {

            $machine_name = $request->machine_name;
            $machine_condition = $request->machine_condition;

            //upload document
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/uploads/machines_images');
                $image->move($destinationPath, $name);
                Machines::where('id', $request->id)->update(['image' => $name, 'machine_name' => $machine_name, 'machine_condition' => $machine_condition]);
            } else {
                Machines::where('id', $request->id)->update(['machine_name' => $machine_name, 'machine_condition' => $machine_condition]);
            }


            $output = [
                'success' => true,
                'msg' => __("lang_v1.updated_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        // return view('machines.index');
        return redirect()->back()->with('status', $output);
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('customer.delete')) {
            abort(403, 'Unauthorized action.');
        }


        if (request()->ajax()) {
            try {

                DB::table('machines')->where('id', $id)->delete();

                $output = [
                    'success' => true,
                    'msg' => __("lang_v1.success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }
}
