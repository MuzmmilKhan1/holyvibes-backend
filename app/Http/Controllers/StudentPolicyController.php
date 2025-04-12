<?php

namespace App\Http\Controllers;

use App\Models\StudentPolicy;
use Illuminate\Http\Request;

class StudentPolicyController extends Controller
{
    public function create_edit_policy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);
        if ($request->id == 0) {
            $policy = StudentPolicy::create([
                'title' => $request->title,
                'description' => $request->description,
            ]);
            return response()->json([
                'message' => 'Policy created successfully.',
                'policy' => $policy
            ], 201);
        } else {
            $policy = StudentPolicy::find($request->id);
            if (!$policy) {
                return response()->json([
                    'message' => 'Policy not found.'
                ], 404);
            }
            $policy->update([
                'title' => $request->title,
                'description' => $request->description,
            ]);
            return response()->json([
                'message' => 'Policy updated successfully.',
                'policy' => $policy
            ], 200);
        }
    }

    public function get_policy()
    {
        $policy = StudentPolicy::all();
        return response()->json([
            'message' => 'Policies found successfully.',
            'policy' => $policy
        ], 200);
    }

    public function delete_policy($policyID)
    {
        $deletedPolicy = StudentPolicy::find($policyID)->delete();
        return response()->json([
            'message' => 'Policy deleted successfully.',
            'policy' => $deletedPolicy
        ], 200);
    }



}
