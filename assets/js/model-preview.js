import $ from 'jquery'
import * as THREE from 'three'
import {RoomEnvironment} from 'three/examples/jsm/environments/RoomEnvironment.js'
import {OrbitControls} from 'three/examples/jsm/controls/OrbitControls.js'
import {GLTFLoader} from 'three/examples/jsm/loaders/GLTFLoader.js'
import {DRACOLoader} from 'three/examples/jsm/loaders/DRACOLoader.js'
import {VOXLoader, VOXMesh} from 'three/examples/jsm/loaders/VOXLoader.js'
import {MeshoptDecoder} from 'three/examples/jsm/libs/meshopt_decoder.module.js'

function extractModelUrl(element) {
    return element.removeAttributeNode(element.getAttributeNode('data-src')).value
}

function scaleUniformlyTo2x2x2(box) {
    return 1 / Math.max(
        Math.abs(box.min.x),
        Math.abs(box.min.y),
        Math.abs(box.min.z),
        box.max.x,
        box.max.y,
        box.max.z,
    )
}

function initCamera() {
    const camera = new THREE.PerspectiveCamera(50, 1, 0.01, 10)
    camera.position.set(1.75, 0.75, 1.75)
    return camera
}

function initScene() {
    const scene = new THREE.Scene()
    scene.background = new THREE.Color(0xbbbbbb)
    return scene
}

function initControls(camera, renderer) {
    const controls = new OrbitControls(camera, renderer.domElement)
    controls.minDistance = 1.5
    controls.maxDistance = 4.5
    controls.autoRotate = true
    controls.autoRotateSpeed = 10
    return controls
}

function axes(length) {
    const points = []
    points.push(new THREE.Vector3(length, 0, 0))
    points.push(new THREE.Vector3(0, 0, 0))
    points.push(new THREE.Vector3(0, length, 0))
    points.push(new THREE.Vector3(0, 0, 0))
    points.push(new THREE.Vector3(0, 0, length))
    const geometry = new THREE.BufferGeometry().setFromPoints(points)
    const material = new THREE.LineBasicMaterial({color: 0x0000ff})
    return new THREE.Line(geometry, material)
}

function frame(length) {
    const geometry = new THREE.BoxGeometry(length, length, length)
    const material = new THREE.LineBasicMaterial({color: 0x00ffff})
    return new THREE.Line(geometry, material)
}

function displayGltfModel(element) {
    let mixer
    const containerWidth = element.offsetWidth
    const containerHeight = element.offsetHeight
    const modelUrl = extractModelUrl(element)

    const clock = new THREE.Clock()
    const renderer = new THREE.WebGLRenderer({antialias: true})
    renderer.setPixelRatio(window.devicePixelRatio)
    renderer.setSize(containerWidth, containerHeight)
    renderer.toneMapping = THREE.ACESFilmicToneMapping
    renderer.toneMappingExposure = 1
    renderer.outputEncoding = THREE.sRGBEncoding
    element.appendChild(renderer.domElement)

    const camera = initCamera()
    const scene = initScene()
    const environment = new RoomEnvironment()
    const pmremGenerator = new THREE.PMREMGenerator(renderer)
    scene.environment = pmremGenerator.fromScene(environment).texture
    const controls = initControls(camera, renderer)

    const dracoLoader = new DRACOLoader()
        .setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.4.1/')
    const loader = new GLTFLoader()
    loader.setDRACOLoader(dracoLoader)
    loader.setMeshoptDecoder(MeshoptDecoder)
    loader.load(modelUrl, function (gltf) {
        const model = gltf.scene
        const box = new THREE.Box3().setFromObject(model)
        model.scale.setScalar(scaleUniformlyTo2x2x2(box))
        scene.add(model)

        if (gltf.animations.length > 0) {
            mixer = new THREE.AnimationMixer(model)
            mixer.clipAction(gltf.animations[0]).play()
        }
    })

    //TODO test
    scene.add(axes(1.0))
    scene.add(frame(2.0))

    function animate() {
        requestAnimationFrame(animate)
        if (mixer !== undefined) {
            const delta = clock.getDelta()
            mixer.update(delta)
        }
        controls.update()
        renderer.render(scene, camera)
    }

    animate()
}

function displayVoxModel(element) {
    const containerWidth = element.offsetWidth
    const containerHeight = element.offsetHeight
    const modelUrl = extractModelUrl(element)

    const renderer = new THREE.WebGLRenderer()
    renderer.setPixelRatio(window.devicePixelRatio)
    renderer.setSize(containerWidth, containerHeight)
    element.appendChild(renderer.domElement)

    const camera = initCamera()
    const scene = initScene()
    const controls = initControls(camera, renderer)

    const hemisphereLight = new THREE.HemisphereLight(0x888888, 0x444444, 1)
    scene.add(hemisphereLight)
    const directionalLight1 = new THREE.DirectionalLight(0xffffff, 0.75)
    directionalLight1.position.set(1.5, 3, 2.5)
    scene.add(directionalLight1)
    const directionalLight2 = new THREE.DirectionalLight(0xffffff, 0.5)
    directionalLight2.position.set(-1.5, -3, -2.5)
    scene.add(directionalLight2)

    const loader = new VOXLoader()
    loader.load(modelUrl, chunks => {
        const group = new THREE.Group()
        const box = new THREE.Box3()
        const palette = chunks[chunks.length - 1].palette
        chunks.forEach(chunk => {
            chunk.palette = palette
            const mesh = new VOXMesh(chunk)
            box.expandByObject(mesh)
            mesh.visible = false
            group.add(mesh)
        })
        group.name = 'model'
        group.scale.setScalar(scaleUniformlyTo2x2x2(box))
        scene.add(group)
    })

    //TODO test
    scene.add(axes(1.0))
    scene.add(frame(2.0))

    function animate(time) {
        requestAnimationFrame(animate)
        const model = scene.getObjectByName('model')
        if (model !== undefined) {
            const framesCount = model.children.length
            const periodPerFrame = 150
            const period = framesCount * periodPerFrame
            const currentTime = time % period
            const currentFrameIndex = Math.floor(currentTime / periodPerFrame)
            model.children.forEach((frame, index) => frame.visible = index === currentFrameIndex)
        }

        controls.update()
        renderer.render(scene, camera)
    }

    requestAnimationFrame(animate)
}

$(document).ready(function () {
    for (let element of document.getElementsByClassName('model-preview')) {
        const mime = element.getAttributeNode('data-mime').value
        if (mime === 'model/voxel') {
            displayVoxModel(element)
        } else if (mime === 'model/gltf-binary') {
            displayGltfModel(element)
        }
    }
})
