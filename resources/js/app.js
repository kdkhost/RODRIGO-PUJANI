import './bootstrap';

import Alpine from 'alpinejs';
import {
    applyAutoPlaceholders,
    bindDeviceAuditFields,
    bindAuthPasswordToggles,
    bindAuthRememberAndAutofillControl,
    bindRecaptchaForms,
    configureToastr,
    flushPageToasts,
} from './shared/ui';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    configureToastr();
    flushPageToasts();
    applyAutoPlaceholders();
    bindDeviceAuditFields(document);
    bindRecaptchaForms(document);
    bindAuthPasswordToggles(document);
    bindAuthRememberAndAutofillControl(document);
    bindSignaturePads(document);
});

function bindSignaturePads(scope = document) {
    scope.querySelectorAll('[data-signature-pad]').forEach((pad) => {
        if (pad.dataset.ready === 'true') {
            return;
        }

        pad.dataset.ready = 'true';
        const canvas = pad.querySelector('[data-signature-canvas]');
        const payload = pad.querySelector('[data-signature-payload]');
        const error = pad.querySelector('[data-signature-error]');
        const form = pad.closest('form');
        const undoButton = pad.querySelector('[data-signature-undo]');
        const clearButton = pad.querySelector('[data-signature-clear]');
        const eraserButton = pad.querySelector('[data-signature-eraser]');
        if (!canvas || !payload || !form) {
            return;
        }

        const context = canvas.getContext('2d');
        const strokes = [];
        let currentStroke = null;
        let eraser = false;
        let startedAt = 0;

        const pointFromEvent = (event) => {
            const rect = canvas.getBoundingClientRect();
            return {
                x: ((event.clientX - rect.left) / rect.width) * canvas.width,
                y: ((event.clientY - rect.top) / rect.height) * canvas.height,
                t: Date.now(),
            };
        };

        const drawStroke = (stroke) => {
            if (!stroke || stroke.points.length < 1) {
                return;
            }

            context.save();
            context.lineCap = 'round';
            context.lineJoin = 'round';
            context.lineWidth = stroke.eraser ? 22 : 3.2;
            context.globalCompositeOperation = stroke.eraser ? 'destination-out' : 'source-over';
            context.strokeStyle = '#111827';
            context.beginPath();
            context.moveTo(stroke.points[0].x, stroke.points[0].y);
            stroke.points.slice(1).forEach((point) => context.lineTo(point.x, point.y));
            context.stroke();
            context.restore();
        };

        const redraw = () => {
            context.clearRect(0, 0, canvas.width, canvas.height);
            strokes.forEach(drawStroke);
        };

        const metrics = () => {
            const penStrokes = strokes.filter((stroke) => !stroke.eraser && stroke.points.length > 1);
            const points = penStrokes.flatMap((stroke) => stroke.points);
            if (points.length === 0) {
                return { stroke_count: 0, point_count: 0, distance_px: 0, duration_ms: 0, bounds: { x: 0, y: 0, width: 0, height: 0 } };
            }

            let distance = 0;
            penStrokes.forEach((stroke) => {
                stroke.points.forEach((point, index) => {
                    if (index === 0) return;
                    const previous = stroke.points[index - 1];
                    distance += Math.hypot(point.x - previous.x, point.y - previous.y);
                });
            });
            const xs = points.map((point) => point.x);
            const ys = points.map((point) => point.y);
            const minX = Math.min(...xs);
            const minY = Math.min(...ys);
            const maxX = Math.max(...xs);
            const maxY = Math.max(...ys);
            const first = Math.min(...points.map((point) => point.t));
            const last = Math.max(...points.map((point) => point.t));

            return {
                stroke_count: penStrokes.length,
                point_count: points.length,
                distance_px: Math.round(distance * 100) / 100,
                duration_ms: Math.max(0, last - first),
                bounds: {
                    x: Math.round(minX),
                    y: Math.round(minY),
                    width: Math.round(maxX - minX),
                    height: Math.round(maxY - minY),
                },
            };
        };

        const sync = () => {
            const currentMetrics = metrics();
            payload.value = JSON.stringify({
                data_url: canvas.toDataURL('image/png'),
                metrics: currentMetrics,
            });

            return currentMetrics;
        };

        const clearError = () => {
            if (error) {
                error.textContent = '';
            }
        };

        canvas.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            clearError();
            startedAt = Date.now();
            currentStroke = { eraser, points: [pointFromEvent(event)] };
            canvas.setPointerCapture?.(event.pointerId);
        });

        canvas.addEventListener('pointermove', (event) => {
            if (!currentStroke) {
                return;
            }

            event.preventDefault();
            currentStroke.points.push(pointFromEvent(event));
            redraw();
            drawStroke(currentStroke);
        });

        const finishStroke = () => {
            if (!currentStroke) {
                return;
            }

            if (currentStroke.points.length === 1) {
                currentStroke.points.push({ ...currentStroke.points[0], x: currentStroke.points[0].x + 0.1, t: Date.now() });
            }
            currentStroke.points[currentStroke.points.length - 1].t = Date.now();
            currentStroke.started_at = startedAt;
            strokes.push(currentStroke);
            currentStroke = null;
            redraw();
            sync();
        };

        canvas.addEventListener('pointerup', finishStroke);
        canvas.addEventListener('pointercancel', finishStroke);
        canvas.addEventListener('pointerleave', finishStroke);

        undoButton?.addEventListener('click', () => {
            strokes.pop();
            redraw();
            sync();
            clearError();
        });

        clearButton?.addEventListener('click', () => {
            strokes.splice(0, strokes.length);
            redraw();
            sync();
            clearError();
        });

        eraserButton?.addEventListener('click', () => {
            eraser = !eraser;
            eraserButton.classList.toggle('active', eraser);
            eraserButton.textContent = eraser ? 'Caneta' : 'Borracha';
        });

        form.addEventListener('submit', (event) => {
            const currentMetrics = sync();
            if (
                currentMetrics.stroke_count < 1
                || currentMetrics.point_count < 12
                || currentMetrics.distance_px < 80
                || currentMetrics.bounds.width < 50
                || currentMetrics.bounds.height < 8
            ) {
                event.preventDefault();
                if (error) {
                    error.textContent = 'Desenhe sua assinatura completa. Pontos ou riscos mínimos não são aceitos.';
                }
                canvas.focus();
            }
        });

        sync();
    });
}
