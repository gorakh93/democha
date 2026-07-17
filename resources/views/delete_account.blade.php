
 @include('header')

<div class="section_premiun_store">
    <div class="premiun_store_box">
          <h2 class="heading">Account delete</h2>
          <div class="premium_child" style="margin-bottom: 50px;">

            <p><br> <br><br>@if($userid)
            Account of userid {{$userid}} deleted successfully <br><br>
            @else
            Account of userid {{$userid}} already deleted

            @endif

            </p><br><br>

          </div>
    </div>
</div>


@include('footer')

