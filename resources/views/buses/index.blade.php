@extends('layouts.app')

@section('title', 'Avtobuslar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('buses.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Excel - dən Yüklə
        </a>
    </div>
</div>

<!-- Nəticələr -->
<div id="searchResults">
    @include('buses.partials.table', ['buses' => $buses])
</div>

<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $buses->withQueryString()->links() }}
</div>
@endsection

@section('scripts')
<script>
    let searchTimeout;

    function filterTable() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            const inputs = document.querySelectorAll('#searchResults input');
            const params = new URLSearchParams();

            inputs.forEach(input => {
                if (input.value) {
                    params.append(input.name, input.value);
                }
            });

            fetch(window.location.origin + '/buses/search?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const newTbody = tempDiv.querySelector('tbody');

                    if (newTbody) {
                        const existingTbody = document.querySelector('#searchResults tbody');
                        if (existingTbody) {
                            existingTbody.innerHTML = newTbody.innerHTML;
                        }
                    }

                    attachInputEvents();
                })
                .catch(error => console.error('Xəta:', error));
        }, 200);
    }

    function attachInputEvents() {
        const inputs = document.querySelectorAll('#searchResults input');
        inputs.forEach(input => {
            input.removeEventListener('input', filterTable);
            input.addEventListener('input', filterTable);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        attachInputEvents();
    });
</script>
@endsection
