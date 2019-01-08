<?php

namespace App\Http\Controllers;

use App\BookingGuest;
use App\BookingPayment;
use App\PaymentMethod;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Hotel;
use App\Tag;
use App\Category;
use App\PostTag;
use App\HotelRoom;
use App\RoomType;
use App\HotelFacility;
use App\HotelRoomFacility;
use App\HotelImage;
use App\Booking;
use App\User;
use App\HotelComment;
use Session;
use Purifier;
use Image;
use Auth;
use Redirect;
use DB;

class HotelController extends Controller
{

    public function __construct() {
        //$this->middleware(['auth']); //open to public
    }

 
    public function show($id)
    {
        $hotel = Hotel::find($id);
        $rate = Hotel::rate($id);

        $data = self::HotelGrid();

        $categories = $data['categories'];
        $tags = $data['tags'];
        $stars = $data['stars'];
        $room_types = $data['room_types'];
        $people_limits = $data['people_limits'];
        $cat_list = $data['cat_list'];
        $tag_list = $data['tag_list'];
        $hotel_facility_list = $data['hotel_facility_list'];
        $hotel_facility_label = $data['hotel_facility_label'];
        $hotel_fontawesome = $data['hotel_fontawesome'];
        $room_type_list = $data['room_type_list'];

        $facility_table = new HotelRoomFacility;
        $room_facility_list = $facility_table->getTableColumns();
        $temp_fontawesome = $facility_table->getFontAwesome();

        $room_facility_label = array();
        foreach ($room_facility_list as $facilities){
            $facilities = str_replace("_"," ",$facilities);
            $room_facility_label[] = ucwords($facilities);
        }

        //print_r($room_type_list);die();

        $user = User::all();
        $user_list = \App\Helpers\AppHelper::instance()->ObjectToArrayMap($user,'id','name');
        
        return view('hotel.show')
            ->withHotel($hotel)
            ->with('categories',$categories)
            ->withTags($tags)
            ->withStars($stars)
            ->withRoomTypes($room_types)
            ->with('people_limits', $people_limits)
            ->with('cat_list',$cat_list)
            ->with('tag_list',$tag_list)
            ->with('hotel_facility_list',$hotel_facility_list)
            ->with('hotel_facility_label',$hotel_facility_label)
            ->with('hotel_fontawesome', $hotel_fontawesome)
            ->with('room_type_list', $room_type_list)
            ->with('room_facility_list',$room_facility_list)
            ->with('temp_fontawesome',$temp_fontawesome)
            ->with('user_list',$user_list)
            ->with('rate',$rate)
            ->with('room_facility_label',$room_facility_label);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $post = hotel::find($id);

        $post->delete();

        Session::flash('success', 'The hotel was successfully deleted.');
        return redirect()->route('hotel.index');
    }
    
    public function delete($id)
    {
        $hotel = Hotel::find($id);

        $hotel->soft_delete = 1;
        $hotel->save();

        Session::flash('success', 'The hotel was successfully deleted.');
        return redirect()->route('hotel.index');
    }



    public function room($id)
    {
        if(self::CheckPermission() == false){ return redirect('auth/login');};

        $hotel = Hotel::find($id);
        $types = RoomType::all();
          
        $type_list = \App\Helpers\AppHelper::instance()->ObjectToArrayMap($types,'id','type');

        $facility_table = new HotelRoomFacility;
        $room_facility_list = $facility_table->getTableColumns();

        $temp_fontawesome = $facility_table->getFontAwesome();

        $room_facility_label = array();
        foreach ($room_facility_list as $facilities){
            $facilities = str_replace("_"," ",$facilities);
            $room_facility_label[] = ucwords($facilities);
        }

//        print_r($room_facility_list);
//        die();


        return view('hotel.room')
            ->withHotel($hotel)
            ->with('type_list',$type_list)
            ->with('room_facility_list',$room_facility_list)
            ->with('temp_fontawesome',$temp_fontawesome)
            ->with('room_facility_label',$room_facility_label);
    }

    
    public function roomcreate($id)
    {
        if(self::CheckPermission() == false){ return redirect('auth/login');};

        $hotel = Hotel::find($id);
        $types = RoomType::all();
        $type_list = \App\Helpers\AppHelper::instance()->ObjectToArrayMap($types,'id','type');
        
        //print_r($types[0]['id']);die();
        $type_lefts = array(); 
        $counter = 0;
        foreach($types as $key => $type){
            $exist_room = HotelRoom::where('room_type_id','=',$type['id'])->where('hotel_id','=',$hotel->id)->get();
            if(!count($exist_room)){
                $type_lefts[$counter]['id'] = $type['id'];
                $type_lefts[$counter]['type'] = $type['type'];
                $counter++;
            }
        }
        
        $facility_table = new HotelRoomFacility;
        $room_facility_list = $facility_table->getTableColumns();

        $room_facility_label = array();
        foreach ($room_facility_list as $facilities){
            $facilities = str_replace("_"," ",$facilities);
            $room_facility_label[] = ucwords($facilities);
        }

        $temp_fontawesome = $facility_table->getFontAwesome();
        
        return view('hotel.roomcreate')
                ->withHotel($hotel)
                ->with('type_list',$type_list)
                ->with('types',$types)
                ->with('type_lefts',$type_lefts)
                ->with('room_facility_list', $room_facility_list)
                ->with('room_facility_label',$room_facility_label)
                ->with('temp_fontawesome', $temp_fontawesome);
    }

    
    
    public function roomstore(Request $request,$id)
    {
        // validate the data
        $this->validate($request, array(
                'room_type_id'         => 'required',
                'ppl_limit'          => 'required|integer',
                'price'         => 'required|integer',
                'qty'   => 'required|integer',
                'availability'          => 'required'
            ));
        
        $room = new HotelRoom();
        $room->hotel_id = $id;
        $room->room_type_id = $request->room_type_id;
        $room->ppl_limit = $request->ppl_limit;
        $room->price = $request->price;
        $room->qty = $request->qty;
        $room->availability = $request->availability;
        $room->promo = $request->promo;
        $room->save();
        
        $facility_table = new HotelRoomFacility;
        $room_facility_list = $facility_table->getTableColumns();
        
        $facility = new HotelRoomFacility();
        $facility->hotel_room_id = $room->id;
        foreach ($room_facility_list as $sv_room_fac){
            $facility->$sv_room_fac = $request->$sv_room_fac;
        }
        $facility->save();
      
        Session::flash('success', 'The room was successfully created!');

        return redirect()->route('hotel.room',$id);
    }

    
    public function roomedit($id,$roomid)
    {
        if(self::CheckPermission() == false){ return redirect('auth/login');};

        $room = HotelRoom::find($roomid);
        $hotel = Hotel::find($id);
        $types = RoomType::all();
        $type_list = \App\Helpers\AppHelper::instance()->ObjectToArrayMap($types,'id','type');
        
        //print_r($room->facility);die();
        
        $type_lefts = array(); 
        $counter = 0;
        foreach($types as $key => $type){
            $exist_room = HotelRoom::where('room_type_id','=',$type['id'])->where('hotel_id','=',$hotel->id)->get();
            if(!count($exist_room)){
                $type_lefts[$counter]['id'] = $type['id'];
                $type_lefts[$counter]['type'] = $type['type'];
                $counter++;
            }
        }
        
        $room_facility_selected = HotelRoomFacility::where('hotel_room_id', '=', $roomid)->first();
        
        $facility_table = new HotelRoomFacility;
        $room_facility_list = $facility_table->getTableColumns();

        $room_facility_label = array();
        foreach ($room_facility_list as $facilities){
            $facilities = str_replace("_"," ",$facilities);
            $room_facility_label[] = ucwords($facilities);
        }

        $temp_fontawesome = $facility_table->getFontAwesome();

        //print_r($room_facility_label);die();
        
        
        return view('hotel.roomedit')
                ->withHotel($hotel)
                ->with('type_list',$type_list)
                ->with('types',$types)
                ->with('type_lefts',$type_lefts)
                ->withRoom($room)
                ->with('room_facility_list',$room_facility_list)
                ->with('room_facility_selected',$room_facility_selected)
                ->with('room_facility_label',$room_facility_label)
                ->with('temp_fontawesome',$temp_fontawesome);
    }
    
    
    public function roomupdate(Request $request, $id, $roomid)
    {
        
        // validate the data
        $this->validate($request, array(
                'room_type_id'         => 'required',
                'ppl_limit'          => 'required|integer',
                'price'         => 'required|integer',
                'qty'   => 'required|integer',
                'availability'          => 'required'
            ));
        
        
        $room = HotelRoom::find($roomid);
        $room->room_type_id = $request->room_type_id;
        $room->ppl_limit = $request->ppl_limit;
        $room->price = $request->price;
        $room->qty = $request->qty;
        $room->availability = $request->availability;
        $room->promo = $request->promo;
        $room->save();
        

        //Update Romm Facilities in loop        
        $facility_table = new HotelRoomFacility;
        $room_facility_list = $facility_table->getTableColumns();
        
        $fac = HotelRoomFacility::where('hotel_room_id', '=', $roomid)->first();
        foreach ($room_facility_list as $sv_room_fac){
            $fac->$sv_room_fac = $request->$sv_room_fac;
        }
        $fac->save();
        
        
        return redirect()->route('hotel.roomedit',['id'=> $id,'roomid' => $roomid]);
    }

    public function roomdelete($id,$roomid)
    {

        $room = HotelRoom::find($roomid);
        $room->delete();

        Session::flash('success', 'The room was successfully deleted.');
        return redirect()->route('hotel.room',$id);
    }


    public function allhotels()
    {
        $data = self::HotelGrid();

        $hotels = Hotel::select('*');
        $hotels = $hotels->paginate(10);
        foreach($hotels as $k => $hotel){
            $rate[] = Hotel::rate($hotel->id);
        }

        $categories = $data['categories'];
        $tags = $data['tags'];
        $stars = $data['stars'];
        $room_types = $data['room_types'];
        $people_limits = $data['people_limits'];
        $cat_list = $data['cat_list'];
        $tag_list = $data['tag_list'];
        $hotel_facility_list = $data['hotel_facility_list'];
        $hotel_facility_label = $data['hotel_facility_label'];
        $hotel_fontawesome = $data['hotel_fontawesome'];
        $room_type_list = $data['room_type_list'];
        $search_small = true;


        return view('pages.welcome')
            ->with('hotels', $hotels)
            ->with('categories',$categories)
            ->withTags($tags)
            ->withStars($stars)
            ->withRoomTypes($room_types)
            ->with('people_limits', $people_limits)
            ->with('cat_list',$cat_list)
            ->with('tag_list',$tag_list)
            ->with('hotel_facility_list',$hotel_facility_list)
            ->with('hotel_facility_label',$hotel_facility_label)
            ->with('hotel_fontawesome', $hotel_fontawesome)
            ->with('room_type_list', $room_type_list)
            ->with('rate',$rate)
            ->with('search_small', $search_small);
    }


    public function HotelGrid(){
        $data = app('App\Http\Controllers\PagesController')->HotelGrid();

        return $data;
    }
    
    
    public function book($id,$roomid)
    {
        //$id = hotel's id;
        if(!Auth::check()){
            Session::flash('success', 'Please login!');
            return redirect('auth/login');
        }
        
        $hotel = Hotel::where('id','=',$id)->first();
        $room = HotelRoom::where('id','=',$roomid)->where('hotel_id','=',$id)->first();

        if($room == null){
            return redirect('/');
        }

        $room_type_list = \App\Helpers\AppHelper::instance()->ObjectToArrayMap(RoomType::all(),'id','type');
        $payment_method = PaymentMethod::all();

        $today = date('Y-m-d');

        $user_id = Auth::user()->id;
        $user = User::where('id','=',$user_id)->first();

        return view('hotel.book')
            ->with('room',$room)
            ->with('room_type_list', $room_type_list)
            ->with('hotel',$hotel)
            ->with('today', $today)
            ->with('user',$user)
            ->with('payment_method', $payment_method);
    }


    public function booking(Request $request,$id,$roomid)
    {

        //$post->name = $request->title;
        $this->validate($request, array(
            'name'         => 'required',
        ));

        sleep(5);

        $booking = new Booking;
        $booking->user_id = Auth::user()->id;
        $booking->hotel_id = $id;
        $booking->hotel_room_id = $roomid;
        $booking->people = count($request->name);
        $booking->in_date = $request->in_date;
        $booking->out_date = $request->out_date;
        $booking->book_date = date('Y-m-d H:i:s');
        $booking->total_price = $request->total_price;
        $booking->payment_method_id = $request->payment_method;
        $booking->approved = 1;
        $booking->status = 1;
        $booking->save();

        if($booking->save()){

            $payment = new BookingPayment();
            $payment->booking_id = $booking->id;
            $payment->user_id = $booking->user_id;
            $payment->single_price = $request->single_price;
            $payment->handling_price = $request->handling_price;
            $payment->total_price = $booking->total_price;
            $payment->payment_method_id = $request->payment_method;
            $payment->card_number = $request->card_number;
            $payment->expired_date = $request->expired_date;
            $payment->security_number = $request->security_number;
            if($request->payment_method == 5) {
                $payment->status = 0;
            } else {
                $payment->status = 1;
            }
            $payment->save();

            if($payment->save()){

                for ($x = 0; $x < count($request->name); $x++) {
                    $guest = new BookingGuest();
                    $guest->booking_id = $booking->id;
                    $guest->name = $request->name[$x];
                    $guest->phone = $request->phone[$x];

                    $c = $x + 1;
                    if($request['gender'.$c][0] != null){
                        $gender = $request['gender'.$c][0];
                    } else {
                        $gender = '';
                    }

                    $guest->gender = $gender;
                    $guest->email = $request->email[$x];
                    $guest->save();
                }

                return redirect('booklist');
            }
        }


        Session::flash('success', 'Sucess booking!');

        //return redirect()->route('hotel.index', $post->id);
    }

    public function booklist()
    {

        if(!Auth::check()){
            return redirect('auth/login');
        } else {
            $user_id = Auth::user()->id;
        };

        $booking = Booking::where('user_id', '=', $user_id)->orderby('in_date','DESC')->get();
        $room_type_list = \App\Helpers\AppHelper::instance()->ObjectToArrayMap(RoomType::all(),'id','type');
        $pay_method_list = \App\Helpers\AppHelper::instance()->ObjectToArrayMap(PaymentMethod::all(),'id','type');

        $room_type = null;
        $pay_method = null;

        if($booking != null) {
            foreach ($booking as $k => $bk) {
                $room[$k] = HotelRoom::where('id', '=', $bk->hotel_room_id)->get()->toarray();
                $room_type[$k] = $room_type_list[$room[$k][0]['room_type_id']];
                $pay_method[$k] = $pay_method_list[$bk->payment['payment_method_id']];
            }
        }

        //print_r($pay_method);die();

        return view('pages.booklist')
            ->with('room_type',$room_type)
            ->with('room_type_list', $room_type_list)
            ->with('pay_method', $pay_method)
            ->with('booking', $booking);
    }

    public function Payment()
    {

        if(!Auth::check()){
            return redirect('auth/login');
        } else {
            $user_id = Auth::user()->id;
        };

        $booking = Booking::where('user_id', '=', $user_id)->orderby('in_date','DESC')->get();
        $room_type_list = \App\Helpers\AppHelper::instance()->ObjectToArrayMap(RoomType::all(),'id','type');
        $pay_method_list = \App\Helpers\AppHelper::instance()->ObjectToArrayMap(PaymentMethod::all(),'id','type');

        $room_type = null;
        $pay_method = null;

        if($booking != null) {
            foreach ($booking as $k => $bk) {
                $room[$k] = HotelRoom::where('id', '=', $bk->hotel_room_id)->get()->toarray();
                $room_type[$k] = $room_type_list[$room[$k][0]['room_type_id']];
                $pay_method[$k] = $pay_method_list[$bk->payment['payment_method_id']];
            }
        }

        //print_r($pay_method);die();

        return view('pages.payment')
            ->with('room_type',$room_type)
            ->with('room_type_list', $room_type_list)
            ->with('pay_method', $pay_method)
            ->with('booking', $booking);
    }
    
    public function Comment(Request $request,$id)
    {
        
        $this->validate($request, [
            'comment' => 'required',
            'rating' => 'required',
        ]);

        if(!Auth::check()){
            return redirect('auth/login');
        } else {
            $user_id = Auth::user()->id;
        };

        
        $comment = new HotelComment();
        $comment->hotel_id = $id;
        $comment->user_id = $user_id;
        $comment->comment = trim($request->comment);
        $comment->star = $request->rating;
        $comment->status = 1;
        $comment->save();

        if($comment->save()){
            Session::flash('success', 'Comment successful!');
            return redirect('hotel/'.$id);
        }


        return view('pages.account')->with('user',$user);
    }
    
    
    

    public function CheckPermission(){

        if(Auth::check()){
            if(Auth::user()->role != 'superadmin'){
                return false;
            }
        } else {
            return false;
        }

        return true;
    }


}
