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
<script>
    let searchTimeout;

    function filterTable() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            const inputs = document.querySelectorAll('#busTableFilter input');
            const params = new URLSearchParams();

            inputs.forEach(input => {
                if (input.value) {
                    params.append(input.name, input.value);
                }
            });

            fetch('/buses/search?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTbody = doc.querySelector('tbody');
                    const newPagination = doc.querySelector('.pagination-wrapper');

                    if (newTbody) {
                        document.querySelector('#searchResults tbody').innerHTML = newTbody.innerHTML;
                    }
                    if (newPagination) {
                        document.querySelector('.pagination-wrapper').innerHTML = newPagination.innerHTML;
                    }
                })
                .catch(error => console.error('Xəta:', error));
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('#busTableFilter input');
        inputs.forEach(input => {
            input.addEventListener('input', filterTable);
        });
    });
</script>
@endsection


