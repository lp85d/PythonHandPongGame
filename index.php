<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hand Pong Game</title>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/hands"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils"></script>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        #game-container {
            position: relative;
        }
        #speed {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        #output_canvas {
            border: 2px solid black;
        }
    </style>
</head>
<body>
    <div id="game-container">
        <h1>Hand Pong Game <input type="range" id="speed" min="1" max="10" value="4"></h1>
        <video id="input_video" style="display:none;"></video>
        <canvas id="output_canvas" width="800" height="600"></canvas>
    </div>

    <script>
        const videoElement = document.getElementById('input_video');
        const canvasElement = document.getElementById('output_canvas');
        const canvasCtx = canvasElement.getContext('2d');
        const speedSlider = document.getElementById('speed');

        const width = 800;
        const height = 600;
        const radius = 15;

        let ballPos = { x: width / 2, y: height / 2 };
        let ballVelocity = { x: parseInt(speedSlider.value), y: parseInt(speedSlider.value) };

        function vecSubtract(v1, v2) {
            return { x: v1.x - v2.x, y: v1.y - v2.y };
        }

        function vecAdd(v1, v2) {
            return { x: v1.x + v2.x, y: v1.y + v2.y };
        }

        function vecMultiply(v, scalar) {
            return { x: v.x * scalar, y: v.y * scalar };
        }

        function vecLength(v) {
            return Math.sqrt(v.x * v.x + v.y * v.y);
        }

        function vecNormalize(v) {
            const len = vecLength(v);
            return len > 0 ? vecMultiply(v, 1 / len) : { x: 0, y: 0 };
        }

        function vecDot(v1, v2) {
            return v1.x * v2.x + v1.y * v2.y;
        }

        function reflectVector(velocity, normal) {
            const dotProduct = vecDot(velocity, normal);
            return vecSubtract(velocity, vecMultiply(normal, 2 * dotProduct));
        }

        function processFrame(hands) {
            // Отображаем видео с камеры зеркально
            canvasCtx.save();
            canvasCtx.scale(-1, 1); // Инвертируем по горизонтали
            canvasCtx.translate(-width, 0); // Смещаем изображение обратно на место
            canvasCtx.drawImage(videoElement, 0, 0, width, height);
            canvasCtx.restore();

            // Обрабатываем взаимодействие с руками
            if (hands.length === 2) {
                const finger1 = hands[0];
                const finger2 = hands[1];

                canvasCtx.beginPath();
                canvasCtx.moveTo(finger1.x, finger1.y);
                canvasCtx.lineTo(finger2.x, finger2.y);
                canvasCtx.strokeStyle = 'green';
                canvasCtx.lineWidth = 5;
                canvasCtx.stroke();

                const lineVector = vecSubtract(finger2, finger1);
                const normalizedLine = vecNormalize(lineVector);

                const ballToLineStart = vecSubtract(ballPos, finger1);
                const projectionLength = vecDot(ballToLineStart, normalizedLine);
                const projectionPoint = vecAdd(finger1, vecMultiply(normalizedLine, projectionLength));

                const distanceToLine = vecLength(vecSubtract(ballPos, projectionPoint));

                if (0 <= projectionLength && projectionLength <= vecLength(lineVector) &&
                    distanceToLine <= radius) {
                    const lineNormal = { x: -normalizedLine.y, y: normalizedLine.x };
                    ballVelocity = reflectVector(ballVelocity, lineNormal);
                }
            }

            // Перемещаем шарик
            ballPos = vecAdd(ballPos, ballVelocity);

            // Проверка столкновений с границами экрана
            if (ballPos.x <= radius || ballPos.x >= width - radius) {
                ballVelocity.x = -ballVelocity.x;
            }

            if (ballPos.y <= radius || ballPos.y >= height - radius) {
                ballVelocity.y = -ballVelocity.y;
            }

            // Рисуем шарик
            canvasCtx.beginPath();
            canvasCtx.arc(ballPos.x, ballPos.y, radius, 0, 2 * Math.PI);
            canvasCtx.fillStyle = 'red';
            canvasCtx.fill();
        }

        const hands = new Hands({
            locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/hands/${file}`
        });

        hands.setOptions({
            maxNumHands: 2,
            modelComplexity: 1,
            minDetectionConfidence: 0.5,
            minTrackingConfidence: 0.5
        });

        hands.onResults(results => {
            const landmarks = results.multiHandLandmarks.map(handLandmarks => {
                const indexFingerTip = handLandmarks[8];
                return {
                    x: (1 - indexFingerTip.x) * width, // Учитываем зеркальность
                    y: indexFingerTip.y * height
                };
            });

            processFrame(landmarks);
        });

        const camera = new Camera(videoElement, {
            onFrame: async () => {
                await hands.send({ image: videoElement });
            },
            width: width,
            height: height
        });

        camera.start();

        // Изменение скорости шарика при движении ползунка
        speedSlider.addEventListener('input', () => {
            const speed = parseInt(speedSlider.value);
            ballVelocity = { x: speed * Math.sign(ballVelocity.x), y: speed * Math.sign(ballVelocity.y) };
        });
    </script>
</body>
</html>
