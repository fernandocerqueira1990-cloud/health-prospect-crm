@if($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <p class="font-semibold">Revise os dados informados:</p>
        <ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
