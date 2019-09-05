<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
     public function index()
   {
   	$users = User::where('id','!=',Auth::user()->id)->get();
      $query = DB::table('notifications')->select('sender_id','body','created_at')
      ->where('receptor_id','=',Auth::user()->id)->get();
      $emisores = [];

      $tipo = Auth::user()->tipo;
      $n = 0;
      foreach($query as $user){
         $senders = DB::table('users')->select('name')->where('id','=',$user->sender_id)->get();
         $emisores[$n]['name'] = $senders[0]->name;
         $emisores[$n]['sender_id'] = $user->sender_id;
         $emisores[$n]['body'] = $user->body;
         $emisores[$n]['created_at'] = $user->created_at;
         $n++;

      }
   	return view('notification', compact('users','emisores','tipo'));
   }

   public function enviar(Request $request)
   {
   	 Notification::create([
   	 	'sender_id' => Auth::user()->id,
   	 	'receptor_id'=>$request->receptor_id,
   	 	 'body'=>$request->body
   	 ]);

   	 return back()->with('success' , 'se envio la notificacion');
   }
}
