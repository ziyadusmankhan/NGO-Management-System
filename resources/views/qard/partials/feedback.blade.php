@if(session('success'))<div class="qalert qsuccess" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="qalert" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p>Your action has not been completed. Uploaded files must be selected again.</p></div>@endif
