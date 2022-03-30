import $ from 'jquery'
import * as THREE from 'three'
import {RoomEnvironment} from 'three/examples/jsm/environments/RoomEnvironment'
import {OrbitControls} from 'three/examples/jsm/controls/OrbitControls'
import {GLTFLoader} from 'three/examples/jsm/loaders/GLTFLoader'
import {VOXLoader, VOXMesh} from 'three/examples/jsm/loaders/VOXLoader'
import {MeshoptDecoder} from 'three/examples/jsm/libs/meshopt_decoder.module'

const MEGAVOX_SIZE_LIMIT = 126

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
    const camera = new THREE.PerspectiveCamera(50, 1, 0.01, 1000)
    camera.position.set(1.75, 0.75, 1.75)
    return camera
}

function initRenderer(element) {
    const renderer = new THREE.WebGLRenderer({antialias: true})
    renderer.setPixelRatio(window.devicePixelRatio)
    renderer.setSize(element.offsetWidth, element.offsetHeight)
    return renderer;
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

function handleLoadError(element, error) {
    console.error(error)
    const message = error.message ? `Error during model load: ${error.message}` : 'Model load failed'
    const spanElement = document.createElement('span')
    spanElement.innerHTML = message
    element.appendChild(spanElement)
}

function displayGltfModel(element) {
    let mixer
    const modelUrl = extractModelUrl(element)

    const clock = new THREE.Clock()
    const renderer = initRenderer(element);
    renderer.toneMapping = THREE.ACESFilmicToneMapping
    renderer.toneMappingExposure = 1
    renderer.outputEncoding = THREE.sRGBEncoding

    const camera = initCamera()
    const scene = initScene()
    scene.add(axes(1.0))
    scene.add(frame(2.0))
    const environment = new RoomEnvironment()
    const pmremGenerator = new THREE.PMREMGenerator(renderer)
    scene.environment = pmremGenerator.fromScene(environment).texture
    const controls = initControls(camera, renderer)

    const loader = new GLTFLoader()
    loader.setMeshoptDecoder(MeshoptDecoder)
    loader.load(modelUrl, gltf => {
        const model = gltf.scene
        const boundingBox = new THREE.Box3().setFromObject(model)
        model.scale.setScalar(scaleUniformlyTo2x2x2(boundingBox))
        scene.add(model)

        if (gltf.animations.length > 0) {
            mixer = new THREE.AnimationMixer(model)
            mixer.clipAction(gltf.animations[0]).play()
        }
        element.appendChild(renderer.domElement)
        animate()
    }, () => {
    }, error => {
        handleLoadError(element, error);
    })

    function animate() {
        requestAnimationFrame(animate)
        if (mixer !== undefined) {
            const delta = clock.getDelta()
            mixer.update(delta)
        }
        controls.update()
        renderer.render(scene, camera)
    }
}

function displayVoxModel(element) {
    const modelUrl = extractModelUrl(element)
    const renderer = initRenderer(element)
    const camera = initCamera()
    const scene = initScene()
    scene.add(axes(MEGAVOX_SIZE_LIMIT / 2))
    scene.add(frame(MEGAVOX_SIZE_LIMIT))
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
        if (chunks === undefined) {
            handleLoadError(element, new Error('Not a valid VOX file'))
            return
        }
        const group = new THREE.Group()
        const boundingBox = new THREE.Box3()
        const palette = chunks[chunks.length - 1].palette
        chunks.forEach(chunk => {
            chunk.palette = palette
            const mesh = new VOXMesh(chunk)
            boundingBox.expandByObject(mesh)
            mesh.visible = false
            group.add(mesh)
        })

        if (
            boundingBox.max.x - boundingBox.min.x > MEGAVOX_SIZE_LIMIT ||
            boundingBox.max.y - boundingBox.min.y > MEGAVOX_SIZE_LIMIT ||
            boundingBox.max.z - boundingBox.min.z > MEGAVOX_SIZE_LIMIT
        ) {
            handleLoadError(
                element,
                new Error(`Model size exceeds the Megavox limit ${MEGAVOX_SIZE_LIMIT}x${MEGAVOX_SIZE_LIMIT}x${MEGAVOX_SIZE_LIMIT}`)
            )
            return
        }

        group.name = 'model'
        scene.add(group)
        const scale = scaleUniformlyTo2x2x2(boundingBox)
        scene.scale.setScalar(scale)
        controls.maxDistance = 4.5 * (MEGAVOX_SIZE_LIMIT / 2) * scale

        element.appendChild(renderer.domElement)
        requestAnimationFrame(animate)
    }, () => {
    }, error => {
        handleLoadError(element, error);
    })

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
