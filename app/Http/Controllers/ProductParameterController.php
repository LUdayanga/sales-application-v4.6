<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProductParameters;
use Yajra\DataTables\Facades\DataTables;

class ProductParameterController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('product.parameters')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $parameters = ProductParameters::where('is_active', '1')
                ->select(['parameter_name', 'parameter_description', 'id']);

            return Datatables::of($parameters)
                ->addColumn(
                    'action',
                    '@can("product.parameters")
                    <button data-val="{{ $id }}" class="btn btn-xs btn-primary edit_parameter_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                    @can("product.parameters")
                        <button data-val="{{ $id }}" class="btn btn-xs btn-danger delete_parameter_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )
                ->editColumn('parameter_name', function ($row) {
                    return  $row->parameter_name;
                })
                ->editColumn('parameter_description', function ($row) {
                    return  $row->parameter_description;
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('product_parameters.index');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('product.parameters')) {
            abort(403, 'Unauthorized action.');
        }
        
        try {
            if ($request->parameter_id == '') {
                $parameter = new ProductParameters;
                $parameter->parameter_name = $request->name;
                $parameter->parameter_description = $request->description;
                $parameter->is_active = '1';

                $parameter->save();
            } else {
                if($request->delete_parameter == ''){
                    ProductParameters::where('id', $request->parameter_id)->update(['parameter_name' => $request->name, 'parameter_description' => $request->description]);
                }else{
                    ProductParameters::where('id', $request->parameter_id)->update(['is_active' => '0']);
                }
            }

            if($request->delete_parameter == ''){
                $output = [
                    'success' => true,
                    'data' => $parameter,
                    'msg' => __("Successfully Saved")
                ];
            }else{
                $output = [
                    'success' => true,
                    'data' => $parameter,
                    'msg' => __("Successfully Deleted")
                ];
            }
            
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => true,
                'msg' => __("Successfully Saved")
            ];
        }

        return $output;
    }
}
