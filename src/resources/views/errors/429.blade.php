@extends('layouts.error')

@php
$code = 429;
$title = 'Muitas solicitações';
$message = 'Você realizou muitas requisições em um curto período. Tente novamente em alguns instantes.';
@endphp