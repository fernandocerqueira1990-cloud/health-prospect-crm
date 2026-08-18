@if($errors->any())
    <div class="alert-danger" role="alert" aria-live="polite">
        <p class="font-semibold">Revise os dados informados:</p>
        <ul class="mt-1.5 list-disc space-y-0.5 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
