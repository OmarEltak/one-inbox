{{--
  HeronSignal real user monitoring — sessions, frontend errors, failed
  requests, funnels, custom events.

  Loads only when HERONSIGNAL_PUBLIC_KEY is set, so local dev never sends
  noise to production telemetry.

  Also consumes any Session::flash('heron_event', [name, payload]) set on
  the previous request (used by CreateNewUser for signup_completed, etc.)
  and emits it once the tracker is ready.

  Docs: https://heronsignal.com/llms.txt
--}}
@if(config('services.heronsignal.public_key'))
    <script>
        window.heronsignalConfig = {
            publicKey: @json(config('services.heronsignal.public_key')),
            service: @json(config('services.heronsignal.service')),
            environment: @json(config('services.heronsignal.environment'))
        };
    </script>
    <script src="https://api.heronsignal.com/tracker.js" async></script>

    {{-- Livewire browser-event bridge: components can call
         $this->dispatch('heron-event', name: 'foo', payload: [...])
         and the tracker will pick it up. --}}
    <script>
        window.addEventListener('heron-event', function (e) {
            if (!window.heronsignal || !e.detail) return;
            var name = e.detail.name || (Array.isArray(e.detail) && e.detail[0] && e.detail[0].name);
            var payload = e.detail.payload || (Array.isArray(e.detail) && e.detail[0] && e.detail[0].payload) || {};
            if (!name) return;
            try { window.heronsignal.event(name, payload); } catch (_) {}
        });
    </script>

    @if(session()->has('heron_event'))
        @php($__heronFlash = session('heron_event'))
        <script>
            (function () {
                var payload = @json($__heronFlash);
                var tries = 0;
                function send() {
                    if (window.heronsignal && typeof window.heronsignal.event === 'function') {
                        window.heronsignal.event(payload.name, payload.payload || {});
                        return;
                    }
                    if (tries++ < 40) setTimeout(send, 150);
                }
                send();
            })();
        </script>
    @endif
@endif
