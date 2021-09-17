    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">Set up Google Authenticator</div>

                    <div class="panel-body" style="text-align: center;">
                        <p>Set up your two factor authentication by scanning the barcode below. Alternatively, you can use the code {{ $secret }}</p>
                        <div>
                            <img width="200px" height="200px" src="{{$qr}}" alt="">
                        </div>
                        <p>You must set up your Google Authenticator app before continuing. You will be unable to login otherwise</p>
                        <div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>