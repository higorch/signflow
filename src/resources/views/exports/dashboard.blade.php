<table>
    <thead>
        <tr>
            <th>REFERÊNCIA</th>
            <th>TÍTULO</th>
            <th>CATEGORIA</th>
            <th>RESPONSÁVEL</th>
            <th>STATUS</th>
            <th>CRIADO EM</th>
            <th>ATUALIZADO EM</th>
            <th>PRAZO DE ASSINATURA</th>
            <th>EXPIRA EM</th>
            <th>TOTAL DE SIGNATÁRIOS</th>
            <th>ASSINADOS</th>
            <th>REJEITADOS</th>
            <th>PENDENTES</th>
            <th>TEMPO DE APROVAÇÃO (H)</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($reports as $report)
            <tr>
                <td>{{ $report['reference'] }}</td>
                <td>{{ $report['title'] }}</td>
                <td>{{ $report['category'] }}</td>
                <td>{{ $report['owner'] }}</td>
                <td>{{ $report['status'] }}</td>

                <td>{{ $report['created_at'] ?? '---' }}</td>
                <td>{{ $report['updated_at'] ?? '---' }}</td>
                <td>{{ $report['sign_deadline_at'] ?? '---' }}</td>
                <td>{{ $report['expires_at'] ?? '---' }}</td>

                <td>{{ $report['total_signers'] }}</td>
                <td>{{ $report['signed_signers'] }}</td>
                <td>{{ $report['rejected_signers'] }}</td>
                <td>{{ $report['pending_signers'] }}</td>

                <td>{{ $report['approval_time_hours'] ?? '---' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>