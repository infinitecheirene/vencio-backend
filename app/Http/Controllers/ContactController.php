<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Log the incoming request
        Log::info('Contact form submission received', $request->all());

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'submitted_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Save to database
            $contact = Contact::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'subject' => $request->subject,
                'message' => $request->message,
                'submitted_at' => $request->submitted_at ?? now(),
                'status' => 'pending',
            ]);

            Log::info('Contact saved successfully', ['id' => $contact->id]);

            return response()->json([
                'success' => true,
                'message' => 'Message received successfully! We will contact you soon.',
                'data' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'subject' => $contact->subject,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Contact form error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process your message. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        try {
            $contacts = Contact::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $contacts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contacts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function updateStatus($id, Request $request)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->status = $request->status;
            $contact->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Contact deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contact',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}